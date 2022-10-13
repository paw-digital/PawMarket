<?php
namespace Paw;

class Helper
{
    public static function hasContactDetails($user)
    {
		if(!empty($user->email))
			return TRUE;
		if(!empty($user->telegram))
			return TRUE;
		if(!empty($user->discord))
			return TRUE;
		
		return false;
	}
}

?>