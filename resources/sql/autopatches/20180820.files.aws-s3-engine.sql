UPDATE
  {$NAMESPACE}_file.file
SET
  storageEngine = 'aws-s3'
WHERE
  storageEngine = 'aws-sdk'
;
