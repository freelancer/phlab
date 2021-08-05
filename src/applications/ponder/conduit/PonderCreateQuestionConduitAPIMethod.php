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
      'user' => 'optional string',
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

  private function subscribeUser($user, $viewer, $question, $request) {
    $xactions = array();
    $xactions[] = id(new PonderQuestionTransaction())
      ->setTransactionType(PhabricatorTransactions::TYPE_COMMENT)
      ->attachComment(
        id(new PonderQuestionTransactionComment())
          ->setContent("Question created by @{$user}"));

    $editor = id(new PonderQuestionEditor())
      ->setActor($viewer)
      ->setContinueOnNoEffect(true)
      ->setContentSourceFromRequest(new AphrontRequest('example.com', '/'))
      ->setIsPreview(false);

    try {
      $xactions = $editor->applyTransactions($question, $xactions);
    } catch (PhabricatorApplicationTransactionNoEffectException $ex) {
      return id(new PhabricatorApplicationTransactionNoEffectResponse())
        ->setCancelURI($view_uri)
        ->setException($ex);
    }
  }
  protected function execute(ConduitAPIRequest $request) {
    $viewer = $request->getUser();
    $question = PonderQuestion::initializeNewQuestion($viewer);

    $params = $this->arrayFlatten($request->getAllParameters());
    $question->setTitle($params['title']);
    $question->setContent($params['content'] ?? '');

    $question = $question->save();

    if ($params['user']) {
      $this->subscribeUser($params['user'], $viewer, $question, $request);
    }

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
