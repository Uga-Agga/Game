<?php

if ($argc != 4) {
  echo "Usage: $argv[0] subject_file message_file addresses_file\n";
  exit;
}

$fd_subject = fopen($argv[1], "r");       // open file containing the subject
$fd_message = fopen($argv[2], "r");       // open file containing the message
$fd_address = fopen($argv[3], "r");       // open file containing the addresses 

echo "Reading message files.\n\n";

while ($part= fgets($fd_subject, 4096))
  $subject.=$part;  // read subject

while ($part= fgets($fd_message, 4096))
  $message.=$part;  // read message

fclose ($fd_subject);
fclose ($fd_message);

$count = 0;

echo "SENDING THE FOLLOWING MAIL:\n\n";
echo "SUBJECT: $subject\n";
echo $message."\n";

while ($buffer = fgets($fd_address, 4096)){
    if ($buffer) {
      $count++;
      echo "Sending mail number $count to $buffer\n";
      mail(trim($buffer),trim($subject),trim($message));
    }
}

echo "$count mail(s) sent.\n";

fclose($fd_address);

?>