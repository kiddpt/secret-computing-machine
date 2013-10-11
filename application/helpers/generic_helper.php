<?php

	function encode_status($status)
	{
		return json_encode(array('status'=>$status));
	}

	function get_extension($file_name){
		$ext = explode('.', $file_name);
		$ext = array_pop($ext);
		return strtolower($ext);
	}
