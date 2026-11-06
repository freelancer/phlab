<?php

/**
 * The value of a `maniphest:deadline` transaction in `maniphest.edit`.
 *
 * Takes an epoch, or a `YYYY-MM-DD` date which is read as the start of that
 * day in the caller's timezone, which is the epoch the web edit form stores
 * when that user picks that date. `null` removes the deadline.
 */
final class ManiphestDeadlineConduitParameterType
  extends ConduitParameterType {

  // From 2000-01-01, which refuses a date or a day count sent as an epoch
  // (20261120 would be 1970-08-23). The reminder fires a day before the
  // deadline, and its trigger event stores that epoch in an `int(10)
  // unsigned` column, up to 4294967295.
  const MIN_EPOCH = 946684800;
  const MAX_EPOCH = 4295053695;

  protected function getParameterValue(array $request, $key, $strict) {
    $value = parent::getParameterValue($request, $key, $strict);

    if ($value === null) {
      return null;
    }

    if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}\z/', $value)) {
      $value = $this->parseDate($request, $key, $value);
    } else if (is_int($value) ||
        (!$strict && is_string($value) && ctype_digit($value))) {
      // Form-encoded calls send every value as a string.
      $value = $this->parseIntValue($request, $key, $value, $strict);
    } else {
      $this->raiseValidationException(
        $request,
        $key,
        pht(
          'Expected an epoch timestamp (an integer), a date as "YYYY-MM-DD", '.
          'or null to remove the deadline.'));
    }

    if ($value < self::MIN_EPOCH || $value > self::MAX_EPOCH) {
      $this->raiseValidationException(
        $request,
        $key,
        pht(
          'The deadline must be between %s and %s (epoch %d to %d), got %d.',
          gmdate('Y-m-d', self::MIN_EPOCH),
          gmdate('Y-m-d', self::MAX_EPOCH),
          self::MIN_EPOCH,
          self::MAX_EPOCH,
          $value));
    }

    // The web form keeps hours and minutes only, so a deadline with seconds
    // would move the next time anyone saves the task from the form.
    if ($value % 60 !== 0) {
      $this->raiseValidationException(
        $request,
        $key,
        pht(
          'The deadline must be on a whole minute, as the web form sets it; '.
          'got %d (%d seconds past the minute).',
          $value,
          $value % 60));
    }

    return $value;
  }

  private function parseDate(array $request, $key, string $value): int {
    $zone = new DateTimeZone($this->getViewer()->getTimezoneIdentifier());

    // "!" starts the day at midnight, as the web form's "12:00 AM" does.
    $date = DateTime::createFromFormat('!Y-m-d', $value, $zone);

    // DateTime rolls an impossible date over ("2026-02-30" is March 2nd).
    if (!$date || $date->format('Y-m-d') !== $value) {
      $this->raiseValidationException(
        $request,
        $key,
        pht('"%s" is not a date.', $value));
    }

    return (int)$date->format('U');
  }

  protected function getParameterTypeName() {
    return 'epoch|date';
  }

  protected function getParameterFormatDescriptions() {
    return [
      pht('Epoch timestamp, as an integer.'),
      pht(
        'Date as "YYYY-MM-DD", meaning the start of that day in your '.
        'timezone, as the web edit form sets it.'),
      pht('Null, to remove the deadline.'),
    ];
  }

  protected function getParameterExamples() {
    return [
      '1450019509',
      '"2026-11-20"',
      'null',
    ];
  }

}
