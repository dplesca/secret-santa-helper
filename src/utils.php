<?php

class Utils{
	public static function assign_users($users_array){
		$givers     = $users_array;
		$receivers  = $users_array;

		foreach($givers as $uid => $user){
			$not_assigned = true;
			do {
				
				$choice = rand(0, count($receivers) - 1);				
				if($user['email'] !== $receivers[$choice]['email']){
				
					$givers[$uid]['giving_to'] = $receivers[$choice];				
					unset($receivers[$choice]);				
					$receivers = array_values($receivers);				
					$not_assigned = false;
				}else{					
					if(count($receivers) == 1){
						//if last the good old swap
						$givers[$uid]['giving_to'] = $givers[0]['giving_to'];
						$givers[0]['giving_to'] = $givers[$uid];
						$not_assigned = false;
					}
				}
			} while ($not_assigned);			
		}
		
		return $givers;
	}
}