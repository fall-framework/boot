<?php

namespace fall\boot;

interface CommandLineRunnerInterface
{
  function run(array $args = []): void;
}
