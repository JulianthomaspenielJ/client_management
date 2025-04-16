<?php
error_reporting(0);
// error_reporting(E_ALL);

require_once "include/apiResponseGenerator.php";
require_once "include/dbConnection.php";
class CONTACTMODEL extends APIRESPONSE
{
    public $user_name;
    public $email;
    public $created_by;
    public $created_date;
    public $updated_by;
    public $updated_date;
    public $status;

    private function processMethod($data, $loginData)
    {

        switch (REQUESTMETHOD) {
            case 'GET':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "get") {
                    $result = $this->getContactPerson($data, $loginData);
                } else {
                    throw new Exception("Method not allowed!");
                }
                return $result;
                break;
            case 'POST':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] === 'create') {
                    $result = $this->createContactPerson($data, $loginData);
                    return $result;
                } elseif ($urlParam[1] === 'list') {
                    $result = $this->getContactPersonDetails($data, $loginData);
                    return $result;
                } else {
                    throw new Exception("Method not allowed!");
                }
                break;
            case 'PUT':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "update") {
                    $result = $this->updateContactPerson($data, $loginData);
                } else {
                    throw new Exception("Method not allowed!");
                }
                return $result;
                break;
            case 'DELETE':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "delete") {
                    $result = $this->deleteContactPerson($data, $loginData);
                } else {
                    throw new Exception("Method not allowed!");
                }
                return $result;
                break;
            default:
                $result = $this->handle_error();
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
     * Function is to get the for particular record
     *
     * @param array $data
     * @return multitype:
     */
    public function getContactPersonDetails($data, $loginData)
    {
        try {
            $responseArray = "";
            $res = array();
            $db = $this->dbConnect();
            $totalRecordCount = $this->getTotalCount($loginData);
            if (empty($data['page_index']) && $data['page_index'] != 0) {
                throw new Exception("page_index should not be empty");
            }
            if (empty($data['data_length'])) {
                throw new Exception("data_length should not be empty");
            }
            $start_index = $data['page_index'] * $data['data_length'];
            $end_index = $data['data_length'];
            $sql = "SELECT id,user_name,email,phone,created_date, updated_date FROM tbl_contact_person WHERE status = 1 and created_by = " . $loginData['user_id'] . " ORDER BY id ASC LIMIT " . $start_index . "," . $end_index . "";
            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);
            if ($row_cnt > 0) {
                while ($data = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                    // echo json_encode($data);

                    array_push($res, $data);
                }

                $responseArray = array(
                    "pageIndex" => $start_index,
                    "dataLength" => $end_index,
                    "totalRecordCount" => $totalRecordCount,
                    'contactPersonData' => $res,
                );
            }
            if ($responseArray) {
                $resultArray = array(
                    "apiStatus" => array(
                    "code" => "200",
                    "message" => "ContactPerson details fetched successfully",

                    "result" => $responseArray,
                ));
                return $resultArray;
            } else {
                return array(

                    "code" => "404",
                    "message" => "No data found..."

                );
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Function is to get the for particular record
     *
     * @param array $data
     * @return multitype:
     */
    public function getContactPerson($data, $loginData)
    {
        try {
            $id = $data[2];
            $db = $this->dbConnect();
            if (empty($data[2])) {
                throw new Exception("Bad request");
            }

            $responseArray = "";
            $db = $this->dbConnect();
            $sql = "SELECT id,user_name,email,phone,created_date, updated_date FROM tbl_contact_person WHERE status = 1 and created_by = " . $loginData['user_id'] . " and id =$id";
            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);
            if ($row_cnt > 0) {
                $data = mysqli_fetch_array($result, MYSQLI_ASSOC);
                $responseArray = array(
                    'ContactPersonData' => $data,
                );
            }
            if ($responseArray) {
                $resultArray = array(
                    "apiStatus" => array(
                    "code" => "200",
                    "message" => "Contact Person details fetched successfully",

                    "result" => $responseArray,
                ));
                return $resultArray;
            } else {
                return array(
                    "apiStatus" => array(
                    "code" => "404",
                    "message" => "No data found..."

                ));
            }
        } catch (Exception $e) {
            echo "ssdfd";
            throw new Exception($e->getMessage());
        }
    }
    /**
     * Post/Add sale
     *
     * @param array $data
     * @return multitype:string
     */
    private function createContactPerson($data, $loginData)
    {
        try {
            $db = $this->dbConnect();
            $validationData = array("user_name" => $data['user_name'], "email" => $data['email'],"phone" => $data['phone']);
            $this->validateInputDetails($validationData);
            $sql = "SELECT id FROM tbl_contact_person	WHERE status = 1 and created_by = " . $loginData['user_id'] . " and user_name ='" . $data['user_name'] . "'";

            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);

            if ($row_cnt != 0) {
                throw new Exception("ContactPerson username " . $data['user_name'] . " already found");
            }
            $dateNow = date("Y-m-d H:i:s");

            $insertQuery = "INSERT INTO tbl_contact_person (user_name, email,phone,status,created_by, created_date) VALUES ('" . $data['user_name'] . "','" . $data['email'] . "','" . $data['phone'] . "','" . '1' . "','" . $loginData['user_id'] . "','$dateNow')";
            if ($db->query($insertQuery) === true) {
                $db->close();
                $statusCode = "200";
                $statusMessage = "ContactPerson details created successfully";

            } else {
                $statusCode = "500";
                $statusMessage = "Unable to create contactPerson details, please try again later";
            }
            $resultArray = array(
                "apiStatus" => array(
                "code" => $statusCode,
                "message" => $statusMessage

            ));
            return $resultArray;
        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                "code" => "401",
                "message" => $e->getMessage()

            ));
        }
    }

    /**
     * Put/Update a Sale
     *
     * @param array $data
     * @return multitype:string
     */
    private function updateContactPerson($data, $loginData)
    {

        try {
            $db = $this->dbConnect();
            $validationData = array("id" => $data['id'], "user_name" => $data['user_name'], "email" => $data['email'], "phone " => $data['phone']);
            $this->validateInputDetails($validationData);
            $dateNow = date("Y-m-d H:i:s");
            $updateQuery = "UPDATE tbl_contact_person SET user_name = '" . $data['user_name'] . "', email = '" . $data['email'] . "',phone = '" . $data['phone'] . "',updated_by = '" . $loginData['user_id'] . "',updated_date = '$dateNow' WHERE id = " . $data['id'] . "";
            if ($db->query($updateQuery) === true) {
                $db->close();
                $statusCode = "200";
                $statusMessage = "Contact Person details updated successfully";

            } else {
                $statusCode = "500";
                $statusMessage = "Unable to update ContactPerson details, please try again later";
            }
            $resultArray = array(
                "apiStatus" => array(
                "code" => $statusCode,
                "message" => $statusMessage

            ));
            return $resultArray;
        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                "code" => "401",
                "message" => $e->getMessage()

            ));
        }
    }

    private function deleteContactPerson($data, $loginData)
    {
        try {
            $id = $data[2];
            $db = $this->dbConnect();
            if (empty($data[2])) {
                throw new Exception("Bad request id is required");
            }
            $sql = "SELECT id FROM tbl_contact_person	WHERE status = 1 and created_by = " . $loginData['user_id'] . " and id =$id";
            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);
            echo $row_cnt;
            if ($row_cnt == 0) {
                throw new Exception("id " . $id . " not found");
            }
            $deleteQuery = "UPDATE tbl_contact_person set status=0 WHERE id = " . $id . "";
            if ($db->query($deleteQuery) === true) {
                $db->close();
                $statusCode = "200";
                $statusMessage = "ContactPerson details deleted successfully";

            } else {
                $statusCode = "500";
                $statusMessage = "Unable to delete ContactPerson details, please try again later";
            }
            $resultArray = array(
                "apiStatus" => array(
                "code" => $statusCode,
                "message" => $statusMessage

            ));
            return $resultArray;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());

        }
    }
    public function validateInputDetails($validationData)
    {
        foreach ($validationData as $key => $value) {
            if (empty($value) || trim($value) == "") {
                throw new Exception($key . " should not be empty!");
            }
        }
    }
    /**
     * Validate function for sale create
     *
     * @param array $data
     * @throws Exception
     * @return multitype:string NULL
     */

    // public function validateInputDateFormate($validationDateData)
    // {

    //     foreach ($validationDateData as $key => $value) {
    //         if ($this->validateDate($value)) {

    //         } else {
    //             throw new Exception($key . " invalid date format!");
    //         }
    //     }
    // }

    // function validateDate($date, $format = 'Y-m-d')
    // {
    //     $dateTimeObj = DateTime::createFromFormat($format, $date);
    //     return $dateTimeObj && $dateTimeObj->format($format) === $date;
    // }



    private function getTotalCount($loginData)
    {
        try {
            $db = $this->dbConnect();
            $sql = "SELECT * FROM tbl_contact_person WHERE status = 1 and created_by = " . $loginData['user_id'] . "";
            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);
            return $row_cnt;
        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                "result" => "401",
                "message" => $e->getMessage(),
            ));
        }
    }
    private function getTotalPages($dataCount)
    {
        try {
            $pages = null;
            if (MAX_LIMIT) {
                $pages = ceil((int) $dataCount / (int) MAX_LIMIT);
            } else {
                $pages = count($dataCount);
            }
            return $pages;
        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                "result" => "401",
                "message" => $e->getMessage(),
            ));
        }
    }
    // Unautherized api request
    private function handle_error()
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
            return $responseData;
        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                "code" => "401",
                "message" => $e->getMessage()

            ));
        }
    }
}
