<?php

function stringOp($String) {
    $enconding = 'UTF-8';
    if ($String) {
        $tipo = (string) $String;    
        $cutStr = substr($tipo,0,50);
        $upperStr = mb_strtoupper($cutStr,$enconding);
        $ltrimStr = ltrim($upperStr);
        $rtrimStr = rtrim($ltrimStr);
        $replaceStr = str_replace(['-','.'], '',$rtrimStr);
        $replaceStr2 = str_replace(['Ã','Á','ÂÀ', 'À'], 'A',$replaceStr);

        
        return $replaceStr2;
       
      } else {
        return '';

      };



}

