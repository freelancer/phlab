<?php

final class PonderAnswerQuestionConduitAPIMethod extends PonderConduitAPIMethod {
  public function getAPIMethodName(): string {
    return 'ponder.answer.create';
  }

  public function getMethodStatusDescription(): ?string {
    return null;
  }

  public function getMethodStatus(): string {
    return self::METHOD_STATUS_UNSTABLE;
  }

  protected function defineCustomParamTypes(): array {
    return [
      'answer'  => 'required string',
      'question_id' => 'required int',
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
      'Answer a question on ponder.');
  }

  private function getQuestion(int $question_id, $viewer): PonderQuestion {
    $question = id(new PonderQuestionQuery())
      ->setViewer($viewer)
      ->withIDs(array($question_id))
      ->needAnswers(true)
      ->executeOne();

    if (!$question) {
      throw new Exception('missing question');
    }

    return $question;
  }
  protected function execute(ConduitAPIRequest $request) {
    $params = $this->arrayFlatten($request->getAllParameters());
    $viewer = $request->getUser();
    $question = $this->getQuestion($params['question_id'], $viewer);
    $answer = PonderAnswer::initializeNewAnswer($viewer, $question);

    $answer->setContent($params['answer']);

    $answer = $answer->save();

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
