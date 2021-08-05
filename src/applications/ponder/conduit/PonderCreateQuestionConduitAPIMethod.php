<?php

final class PonderCreateQuestionConduitAPIMethod extends PonderConduitAPIMethod {
  public function getAPIMethodName(): string {
    return 'ponder.question.create';
  }

  public function getMethodStatusDescription(): ?string {
    return null;
  }

  public function getMethodStatus(): string {
    return self::METHOD_STATUS_UNSTABLE;
  }

  protected function defineCustomParamTypes(): array {
    return [
      'title'  => 'required string',
      'content' => 'optional string',
      'subscribers' => 'optional list<string>',
    ];
  }

  final protected function defineParamTypes() {
    return $this->defineCustomParamTypes();
  }

  protected function defineReturnType(): string {
    return 'map<string, wild>';
  }

  public function getMethodDescription(): string {
    return pht(
      'Create a question on ponder.');
  }

  protected function execute(ConduitAPIRequest $request) {
    $question = PonderQuestion::initializeNewQuestion($request->getUser());

    $params = $this->arrayFlatten($request->getAllParameters());
    $question->setTitle($params['title']);
    $question->setContent(idx($params, 'content', ''));

    $question = $question->save();

    return [
      'id' => $question->getID(),
      'title' => $question->getTitle(),
      'phid' => $question->getPHID(),
      'authorPHID' => $question->getAuthorPHID(),
    ];
  }

  private function arrayFlatten($array = null) {
    $result = array();

    if (!is_array($array)) {
        $array = func_get_args();
    }

    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $result = array_merge($result, $this->arrayFlatten($value));
        } else {
            $result = array_merge($result, array($key => $value));
        }
    }

    return $result;
  }
}
