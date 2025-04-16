<?php
require_once "include/apiResponseGenerator.php";
require_once "include/dbConnection.php";
class REGISTERMODEL extends APIRESPONSE
{
	private function processMethod($data)
	{

		switch (REQUESTMETHOD) {
			case 'GET':
				$data = array(
					'apiStatus'=>array(
					'code' => 405,
					'message' => "GET Method Not Allowed"),
				);
				return $data;
				break;
			
			case 'POST':
				$type = $data['type'];
				if ($type == 'user' || $type == 'admin') {
					$result = $this->userRegistration($data);
				}
				else {
					$result = array(
						"apiStatus"=>array(
						"code" => "404",
						"message" => "Invalid request"),
					);
				}
				return $result;
				break;
			default:
				$result = $this->handle_error($data);
				return $result;
				break;
		}
	}
	// Initiate db connection
	private function dbConnect()
	{
		$conn = new DBCONNECTION();
		$db = $conn->connect();
		return $db;
	}
	/**
	 * Post/Register Member
	 *
	 * @param array $data
	 * @return multitype:string
	 */
	private function userRegistration($data)
	{
		try {
			$db = $this->dbConnect();
			$userData = $data['userData'];
			if ($userData['password'] != $userData['confirmPassword']) {
				throw new Exception("Password & Confirm Password are not correct!");
			}
			$password = $userData['password'];
			$sql = "SELECT id FROM tbl_users WHERE email_id = '" . $userData['emailId'] . "' AND status = 1";
			// echo $sql;exit;
			$result = mysqli_query($db, $sql);
			$row_cnt = mysqli_num_rows($result);
			if ($row_cnt > 0) {
				throw new Exception("User already exist");
			}
			$user_name = isset($userData['user_name']) ? $userData['user_name'] : "";
			$phone = isset($userData['phone']) ? $userData['phone'] : "";
			// $role_id = isset($userData['role_id']) ? $userData['role_id'] : "";

			if (empty($user_name)) {
				throw new Exception("user_name is required");
			}
			elseif(empty($phone)){throw new Exception("phone_number is required");}
			// elseif(empty($role_id)){throw new Exception("role_id is required");}
			
	
		
			$hashed_password = hash('sha256', hash('sha256', $password));

			$insertQuery = "INSERT INTO tbl_users (`user_name`, email_id,`password`, phone) VALUES ('" . $user_name . "','" . $userData['emailId'] . "','" . $hashed_password . "','" . $phone . "')";
			if ($db->query($insertQuery) === TRUE) {
				$lastInsertedId = mysqli_insert_id($db);
				$this->updateUserRole($lastInsertedId);
				$db->close();
			}
			$resultArray = array(
			"apiStatus" => array(
				"code" => "200",
				"message" => "Your registration has submitted Successfully"),
			"result" => array("mailStatus" => ""),
			);
			return $resultArray;
		} catch (Exception $e) {
			return array(
				"apiStatus" =>array(
				"code" => "401",
				"message" => $e->getMessage()),
			);
		}
	}

	private function updateUserRole($lastInsertedId)
	{
		try {
			$db = $this->dbConnect();

			if ($lastInsertedId) {
				$insertQuery = "INSERT INTO tbl_user_role_map (`user_id`, , `created_by`) VALUES ('$lastInsertedId', '$lastInsertedId') ";
				if ($db->query($insertQuery) === TRUE) {
					$db->close();
					return true;
				}
				return false;
			} else {
				throw new Exception("Not able to update role");
			}

		} catch (Exception $e) {
			return array(
				"apiStatus" => array(
				"code" => "401",
				"message" => $e->getMessage()),
			);
		}
	}



// Unautherized api request
private function handle_error($request)
{
}
/**
 * Function is to process the crud request
 *
 * @param array $request
 * @return array
 */
    public function processList($request, $token)
	{
            
		try {
			$responseData = $this->processMethod($request, $token);
			$result = $this->response($responseData);
			return $result;
		} catch (Exception $e) {
			return array(
				"apiStatus" => array(
				"code" => "401",
				"message" => $e->getMessage()),
			);
		}
	}
}


?>