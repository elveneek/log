<?php
namespace Elveneek;
class MetaLogFormatter extends \Monolog\Formatter\LineFormatter{
   public function format(array $record): string
   {
        if (isset($record['context']['exception']) && $record['context']['exception'] instanceof \Throwable) {
             $record['context']['exception'] = $this->normalizeException($record['context']['exception']);
        }
       
        if(isset($record['is_this_first_request'])){
            $isThisFirstRequest = $record['is_this_first_request'];
            unset($record['is_this_first_request']);
        }else{
            $isThisFirstRequest = false;
        }

        $output = '';
        if($isThisFirstRequest){
            $output .= "===================================================================\n";
        }
        $output .= "[".$record['datetime']." ".$record['request_id'];
        
        
        if(function_exists('session_id')){
            $output .=  ' '.session_id();
        }
        
        if(!empty($record["extra"]["file"])){
            $output .=  ' '.$record["extra"]["file"].'#'.$record["extra"]["line"];
        }
        
        
        
		$output .= "]\n";
		$output .= $record['message']."\n";
		
		if(!empty($record['context'])){
            $output .=  json_encode ($record['context'] , JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
        }
		
		
        $output .= "\n";
        return $output;

        $vars = parent::format($record);
        $output = $this->format;
        foreach ($vars['extra'] as $var => $val) {
            if (false !== strpos($output, '%extra.'.$var.'%')) {
                $output = str_replace('%extra.'.$var.'%', $this->stringify($val), $output);
                unset($vars['extra'][$var]);
            }
        }

        foreach ($vars['context'] as $var => $val) {
            if (false !== strpos($output, '%context.'.$var.'%')) {
                $output = str_replace('%context.'.$var.'%', $this->stringify($val), $output);
                unset($vars['context'][$var]);
            }
        }

        if ($this->ignoreEmptyContextAndExtra) {
            if (empty($vars['context'])) {
                unset($vars['context']);
                $output = str_replace('%context%', '', $output);
            }

            if (empty($vars['extra'])) {
                unset($vars['extra']);
                $output = str_replace('%extra%', '', $output);
            }
        }

        foreach ($vars as $var => $val) {
            if (false !== strpos($output, '%'.$var.'%')) {
                $output = str_replace('%'.$var.'%', $this->stringify($val), $output);
            }
        }

        // remove leftover %extra.xxx% and %context.xxx% if any
        if (false !== strpos($output, '%')) {
            $output = preg_replace('/%(?:extra|context)\..+?%/', '', $output);
            if (null === $output) {
                throw new \RuntimeException('Failed to run preg_replace: ' . preg_last_error() . ' / ' . preg_last_error_msg());
            }
        }

        return $output;
    }
}
