<?php
/*
Poor man's Dynamic DNS 

### Mac version: ###
#!/bin/bash
PRIVATE_IP="$(ifconfig |grep 'inet '|grep -v 127| awk '{print $2}')"
curl "https://www.devnet.ca/phonehome.php?internal-ip=${PRIVATE_IP}&host=$(hostname -f)"

### Ubuntu version: ###
#!/bin/bash
PRIVATE_IP="$(ifconfig | awk '/inet addr/{print substr($2,6)}'|grep -v 127.0.0.1)"
curl "https://www.devnet.ca/phonehome.php?internal-ip=${PRIVATE_IP}&host=$(hostname -f)"

### To retreive remote IP info: ###
- `curl https://www.devnet.ca/sites/default/files/<hostname>.yml`


### The php below lives on the server and records remote callers. ###


*/
function main() {
  
  $allowed_hosts = array(
    "optibuntu.mountain.skibaldy.com",
    "danderson-ubuntu.acro.local",
    "le-big-mac.local",
    "nuc",
  );

  $internal_ip = $_GET["internal-ip"];
  if(empty($internal_ip) || filter_var($internal_ip, FILTER_VALIDATE_IP) === false) {
    echo("ERR: Invalid internal-ip.\n");
    http_response_code(403);
    return;
  }
  
  $host = strtolower($_GET["host"]);
  if (empty($host) || !in_array($host, $allowed_hosts)) {
    echo("ERR: specified host not in list of allowed hosts.\n");
    http_response_code(403);
    return;
  }
  
  $path = getcwd() . "/sites/default/files/$host.yml";
  if (file_exists($path)) {
    $old_contents = file_get_contents($path);
    $parsed = yaml_parse($old_contents);
    $first_seen = $parsed["first_seen"];
  }
  else {
    $first_seen = date("Y-m-d H:i:s");
  }
  
  $contents = "---\n";
  $contents .= "host: \"$host\"\n";
  $contents .= "remote_addr: \"" . $_SERVER["REMOTE_ADDR"] . "\"\n";
  $contents .= "internal_ip: \"$internal_ip\"\n";
  $contents .= "first_seen: \"$first_seen\"\n"; 
  $contents .= "last_seen: \"" . date("Y-m-d H:i:s") . "\"\n"; 
  $bytes = file_put_contents($path, $contents);
  if ($bytes === false) {
    echo("ERR: the file could not be saved.\n");
    http_response_code(500);
  }
  else {
    echo("OK: Wrote $bytes bytes.\n");
  }
}
main();
