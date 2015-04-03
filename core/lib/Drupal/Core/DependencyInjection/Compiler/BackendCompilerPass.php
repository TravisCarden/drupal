<?php

/**
 * @file
 * Contains \Drupal\Core\DependencyInjection\Compiler\BackendCompilerPass.
 */

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Defines a compiler pass to allow automatic override per backend.
 *
 * A module developer has to tag his backend service with "backend_overridable":
 * @code
 * custom_service:
 *   class: ...
 *   tags:
 *     - { name: backend_overridable }
 * @endcode
 *
 * As a site admin you set the 'default_backend' in your services.yml file:
 * @code
 * parameters:
 *   default_backend: sqlite
 * @endcode
 *
 * As a developer for alternative storage engines you register a service with
 * $yourbackend.$original_service:
 *
 * @code
 * sqlite.custom_service:
 *   class: ...
 * @endcode
 */
class BackendCompilerPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    $default_backend = $container->hasParameter('default_backend') ? $container->getParameter('default_backend') : NULL;
    // No default backend was configured, so continue as normal.
    if (!isset($default_backend)) {
      return;
    }

    foreach ($container->findTaggedServiceIds('backend_overridable') as $id => $attributes) {
      // If the service is already an alias it is not the original backend, so
      // we don't want to fallback to other storages any longer.
      if ($container->hasAlias($id)) {
        continue;
      }
      if ($container->hasDefinition("$default_backend.$id") || $container->hasAlias("$default_backend.$id")) {
        $container->setAlias($id, new Alias("$default_backend.$id"));
      }
    }
  }

}