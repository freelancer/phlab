<?php

/**
 * Safely substitute variables into a string.
 *
 * This method was copied from
 * @{method:HarbormasterBuildStepImplementation::mergeVariables}.
 *
 * @param  callable  Name of a `vsprintf` function, such as @{function:vurisprintf}.
 * @param  string    User-provided pattern string containing `${variables}`.
 * @param  dict      List of available replacement variables.
 * @return string    String with variables replaced safely into it.
 */
function varsprintf(callable $function, string $pattern, array $variables): string {
  $regexp = '@\\$\\{(?<name>[a-z\\./_-]+)\\}@';

  $matches = [];
  preg_match_all($regexp, $pattern, $matches);

  $argv = array_map(
    function (string $name) use ($variables) {
      if (!array_key_exists($name, $variables)) {
        throw new Exception(pht("No such variable '%s'!", $name));
      }

      return $variables[$name];
    },
    $matches['name']);

  $pattern = str_replace('%', '%%', $pattern);
  $pattern = preg_replace($regexp, '%s', $pattern);

  return $function($pattern, $argv);
}
