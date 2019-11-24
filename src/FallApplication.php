<?php

namespace fall\boot;

use fall\context\annotation\AnnotationConfigApplicationContext;
use fall\context\stereotype\Component;
use fall\core\annotation\Ignore;
use fall\core\annotation\Order;
use fall\core\traits\Builder;
use fall\core\utils\AnnotationUtils;
use fall\core\utils\ClassUtils;

/**
 * @author Angelis <angelis@users.noreply.github.com>
 */
class FallApplication
{

  private $applicationContext;

  public function __construct($source, $args)
  {
    $logo = <<<EOT
  _______  __     ___     ___       
 /"     "|/""\   |"  |   |"  |      
(: ______)    \  ||  |   ||  |      
 \/    |/' /\  \ |:  |   |:  |      
 // ___)/  __'  \ \  |___ \  |___   
(:  ( /   /  \   ( \_|:  ( \_|:  \  
 \__/(___/    \___)_______)_______) 
                                                        
Version 1.0

EOT;
    echo $logo;

    $this->applicationContext = new AnnotationConfigApplicationContext();
    $this->applicationContext->scan();

    foreach (AnnotationUtils::getAllExtendedReflectionClassesUsingTrait(Builder::class) as $extendedReflectionClass) {
      $classBuilder = ClassUtils::buildNewClass($extendedReflectionClass->getShortName() . 'Builder', $extendedReflectionClass->getNamespaceName());

      $classBuilder->setConstructor('public', [], '$this->target = new ' . $extendedReflectionClass->getShortName() . '();');
      $classBuilder->addMethod('build', 'public', [], 'return $this->target;');

      foreach ($extendedReflectionClass->getProperties() as $extendedReflectionProperty) {
        if ($extendedReflectionProperty->isAnnotationPresent(Ignore::class)) {
          continue;
        }

        $classBuilder->addMethod($extendedReflectionProperty->getName(), 'public', ['$value'], '\\fall\\core\\utils\\ReflectionUtils::setFieldValue($this->target, "' . $extendedReflectionProperty->getName() . '", $value); return $this;');
      }

      $classBuilder->build();
    }

    $runners = [];
    foreach (AnnotationUtils::getAllExtendedReflectionClassesHavingAnnotation(Component::class) as $extendedReflectionClass) {
      if ($extendedReflectionClass->implementsInterface(CommandLineRunnerInterface::class)) {
        $order = 999;
        if ($extendedReflectionClass->isAnnotationPresent(Order::class)) {
          $order = $extendedReflectionClass->getAnnotation(Order::class)->value();
        }

        $runners[] = [
          'bean' => $this->applicationContext->getBeanByType($extendedReflectionClass->getName()),
          'order' => $order
        ];
      }
    }

    usort($runners, function ($a, $b) {
      return $a['order'] <=> $b['order'];
    });

    foreach ($runners as $runner) {
      $runner['bean']->run($args);
    }

    // TODO this should be done in a subprojet (fall/boot-mvc or fall/boot-server)
    /*if (!empty(AnnotationUtils::getAllExtendedReflectionClassesHavingAnnotation(Controller::class))) {
      $this->applicationContext->registerBean(HttpServer::class);
      $this->applicationContext->getBeanByType(HttpServer::class)
        ->addOnServerStartCallback(function() {
          echo "Server started and listening\r\n";
        })
        ->start()
      ;
    }*/
  }

  public static function run($source, $args)
  {
    $fallApplication = new FallApplication($source, $args);
  }
}
