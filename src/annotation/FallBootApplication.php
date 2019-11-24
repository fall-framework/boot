<?php

namespace fall\boot\annotation;

use fall\boot\annotation\FallBootConfiguration;
use fall\context\annotation\ComponentScan;
use fall\core\lang\Annotation;

/**
 * @ComponentScan()
 * @FallBootConfiguration()
 * @author Angelis <angelis@users.noreply.github.com>
 */
interface FallBootApplication extends Annotation
{ }
