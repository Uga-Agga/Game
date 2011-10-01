<?
function clean($string){
  return trim(htmlentities(strip_tags($string), ENT_QUOTES));
}

class Params{
  
  function Params(){
    
    $params = array_merge($_GET, $_POST);
    
    foreach ($params as $k=>$v){
      
      if (is_array($v)){
        $array = array();
        foreach ($v as $key => $values)
          $array[$key] = clean($values);
        $this->$k = $array;
      } else {
        $v = clean($v);
        $this->$k = $v;
      }
    }
  }
}
?>