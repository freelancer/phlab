UPDATE
  {$NAMESPACE}_maniphest.maniphest_transaction AS transaction,
  {$NAMESPACE}_maniphest.maniphest_transaction_comment AS comment
SET
  transaction.editPolicy = transaction.authorPHID,
  comment.authorPHID = transaction.authorPHID,
  comment.editPolicy = transaction.authorPHID
WHERE
  transaction.phid = comment.transactionPHID
  AND transaction.transactionType = "core:comment"
  AND transaction.authorPHID != comment.authorPHID
  AND comment.content != ""
;
