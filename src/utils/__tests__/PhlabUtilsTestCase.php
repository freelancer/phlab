<?php

final class PhlabUtilsTestCase extends PhutilTestCase {

  public function testVarsprintf(): void {
    $test_cases = [
      [
        '',
        [],
        '',
      ],
      [
        'Hello World',
        [],
        'Hello World',
      ],
      [
        '${greeting} ${name}',
        [
          'greeting' => 'Hello',
          'name'     => 'Bob',
        ],
        'Hello Bob',
      ],
      [
        'Hello ${name}, %s',
        [
          'name' => 'Bob',
        ],
        'Hello Bob, %s',
      ],
    ];

    foreach ($test_cases as $test_case) {
      list($pattern, $variables, $expected) = $test_case;

      $this->assertEqual(
        $expected,
        varsprintf('vsprintf', $pattern, $variables));
    }
  }

}
