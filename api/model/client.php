<?php
error_reporting(1);

require_once "include/apiResponseGenerator.php";
require_once "model/user.php";
require_once "include/dbConnection.php";

class CLIENTMODEL extends APIRESPONSE
{

    private function processMethod($data, $loginData)
    {
        switch (REQUESTMETHOD) {
            case 'GET':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "get") {
                    if ($urlParam[2] == "clientdroplist") {
                        $result = $this->getClientDrop($data);
                        return $result;
                    }if ($urlParam[2] == "serviceofferedlist") {
                        $result = $this->getServiceDrop($data);
                        return $result;
                    } else {
                        $result = $this->getClient($data, $loginData);
                        return $result;
                    }

                } else {
                    throw new Exception("Method not allowed!");
                }
                break;
            case 'POST':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] === 'create') {
                    $result = $this->createClient($data, $loginData);
                    return $result;
                } elseif ($urlParam[1] === 'list') {
                    $result = $this->getClientDetails($data, $loginData);
                    return $result;
                } else {
                    throw new Exception("Method not allowed!");
                }
                break;
            case 'PUT':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "update") {
                    $result = $this->updateClient($data, $loginData);
                } else {
                    throw new Exception("Method not allowed!");
                }
                return $result;
                break;
            case 'DELETE':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "delete") {
                    $result = $this->deleteClient($data, $loginData);
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
    /**
     * Function is to get the for particular record
     *
     * @param array $data
     * @return multitype:
     */

    public function getClientDrop($data)
    {

        try {
            $responseArray = "";
            $clientDrop = array();
            $db = $this->dbConnect();

            $queryService = "SELECT id, client_name FROM tbl_client WHERE status=1";
            $result = $db->query($queryService);

            $row_cnt = mysqli_num_rows($result);
            while ($data = mysqli_fetch_array($result, MYSQLI_ASSOC)) {

                array_push($clientDrop, $data);
            }

            $responseArray = array(
                "totalRecordCount" => $row_cnt,
                "clientData" => $clientDrop,
            );

            if ($responseArray) {
                $resultArray = array(
                    "apiStatus" => array(
                        "code" => "200",
                        "message" => "Client details fetched successfully",
                    ),
                    "result" => $responseArray,
                );
                // print_r($resultArray);exit;
                return $resultArray;
            } else {
                return array(
                    "apiStatus" => array(
                        "code" => "404",
                        "message" => "No data found...",
                    ),
                );
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getServiceDrop($data)
    {

        try {
            $responseArray = "";
            $serviceDrop = array();
            $db = $this->dbConnect();

            $queryService = "SELECT id, service_name FROM tbl_service_offered WHERE status=1";
            $result = $db->query($queryService);
            $row_cnt = mysqli_num_rows($result);
            while ($data = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                array_push($serviceDrop, $data);
            }

            $responseArray = array(
                "totalRecordCount" => $row_cnt,
                "serviceData" => $serviceDrop,
            );
            if ($responseArray) {
                $resultArray = array(
                    "apiStatus" => array(
                        "code" => "200",
                        "message" => "Service details fetched successfully",
                    ),
                    "result" => $responseArray,
                );
                return $resultArray;
            } else {
                return array(
                    "apiStatus" => array(
                        "code" => "404",
                        "message" => "No data found...",
                    ),
                );
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    //ListClientDetails function start
    public function getClientDetails($data, $loginData)
    {
        try {
            $db = $this->dbConnect();

            if ($data['pageIndex'] == "" && $data['pageIndex'] == "") {
                throw new Exception("pageIndex should not be empty!");
            }
            if ($data['dataLength'] == "" && $data['dataLength'] == "") {
                throw new Exception("dataLength should not be empty!");
            }
            $start_index = $data['pageIndex'] * $data['dataLength'];
            $end_index = $data['dataLength'];

            // Fetch tenant details separately
            $queryServiceTenant = "SELECT tn.id, tn.client_name, tn.email, tn.address, tn.phone
        FROM tbl_client AS tn WHERE tn.status = 1 ORDER BY id ASC
        LIMIT " . $start_index . ", " . $end_index . "";
            $resultTenant = $db->query($queryServiceTenant);
            $row_cnt_tenant = mysqli_num_rows($resultTenant);

            // Fetch user and service details for each tenant
            $clientData = array();
            while ($client = mysqli_fetch_array($resultTenant, MYSQLI_ASSOC)) {
                $userData = array();
                $serviceData = array();

                // Fetch user details
                $queryServiceUsers = "SELECT u.id, u.user_name, u.email_id AS user_email, u.phone AS user_phone
                FROM tbl_client_user_map AS tum
                JOIN tbl_users AS u ON tum.user_id = u.id
                WHERE tum.status = 1 AND u.status = 1 AND tum.client_id =" . $client['id'] . " AND u.created_by = " . $loginData['user_id'];
                $resultUsers = $db->query($queryServiceUsers);
                while ($user = mysqli_fetch_array($resultUsers, MYSQLI_ASSOC)) {
                    $userData = $user;
                }

                // Fetch service details
                $queryService = "SELECT s.id as service_id, s.service_name
                FROM tbl_client_service_map AS csm
                JOIN tbl_service_offered AS s ON csm.service_id = s.id
                WHERE csm.client_id = " . $client['id'] . " AND csm.status = 1";
                $resultService = $db->query($queryService);
                while ($service = mysqli_fetch_array($resultService, MYSQLI_ASSOC)) {
                    $serviceData = $service;
                }

                $clientDetails = array(
                    'id' => $client['id'],
                    'client_name' => $client['client_name'],
                    'email' => $client['email'],
                    'address' => $client['address'],
                    'phone' => $client['phone'],
                    'userData' => $userData,
                    'serviceData' => $serviceData,
                );

                array_push($clientData, $clientDetails);
            }

            $responseArray = array(
                "pageIndex" => $start_index,
                "dataLength" => $end_index,
                "totalRecordCount" => $row_cnt_tenant,
                'clientData' => $clientData,
            );

            if ($clientData) {
                $resultArray = array(
                    "apiStatus" => array(
                        "code" => "200",
                        "message" => "Client with user and service details fetched successfully",
                    ),
                    "result" => $responseArray,
                );
            } else {
                $resultArray = array(
                    "apiStatus" => array(
                        "code" => "404",
                        "message" => "No data found...",
                    ),
                );
            }

            return $resultArray;

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    //ListClientDetails function end

    /**
     * Log create For Login
     *
     * @param string $message
     * @param string $userName
     * @throws Exception
     */
    public function loginLogCreate($message, $userName, $dir)
    {
        try {
            $fp = fopen(LOG_LOGIN, "a");
            $file = $dir;
            fwrite($fp, "" . "\t" . Date("r") . "\t$file\t$userName\t$message\r\n");
        } catch (Exception $e) {
            $this->loginLogCreate($e->getMessage(), "", getcwd());
            return array(
                "apiStatus" => array(
                    "code" => "401",
                    "message" => $e->getMessage(),
                ));
        }
    }

    //This function used for tbl_client_service_map and get the service for included ID
    public function getClientService($userId)
    {
        // print_r($userId);exit;
        try {
            $db = $this->dbConnect();
            $queryService = "SELECT rl.id,rl.service_name,rl.description FROM tbl_client_service_map as csm
            JOIN tbl_service_offered as rl ON csm.service_id=rl.id
            WHERE csm.client_id = '$userId' AND rl.status=1 and csm.status=1";
            // echo $queryService;exit;
            $result = $db->query($queryService);

            $row_cnt = mysqli_num_rows($result);
            // echo $row_cnt;exit;
            if ($row_cnt > 0) {
            }
            $service = array();
            while ($data = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                array_push($service, $data);
            }
            // $service = array('id' => $data['id'],'serviceName' => $data['service_name'], 'description' =>$data['description']);
            return $service;
        } catch (Exception $e) {
            $this->loginLogCreate($e->getMessage(), "", getcwd());
            return array(
                "apiStatus" => array(
                    "code" => "401",
                    "message" => $e->getMessage(),
                ));
        }
    }

    /**
     * Function is to get the for particular record
     *
     * @param array $data
     * @return multitype:
     */

    //GetByIdClient function start
    public function getClient($data)
    {
        // print_r($data);exit;
        try {
            $id = $data[2];
            $db = $this->dbConnect();
            if (empty($data[2])) {
                throw new Exception("Bad request");
            }

            $responseArray = "";
            $db = $this->dbConnect();
            $sql = "SELECT cl.id, cl.client_name, cl.email, cl.phone, cl.created_by, rl.user_name, rl.email_id, cl.address, rl.id as user_id, rl.created_by as user_created_by, so.id as service_id, so.service_name FROM tbl_client_user_map AS csm
            JOIN tbl_users AS rl ON csm.user_id = rl.id JOIN tbl_client AS cl ON csm.client_id = cl.id
            JOIN tbl_client_service_map AS cso ON cso.client_id = cl.id
            JOIN tbl_service_offered AS so ON cso.service_id = so.id WHERE csm.status = 1 AND cl.status = 1 AND cl.id = $id";
            //   print_r($sql);exit;
            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);
            if ($row_cnt > 0) {
                $data = mysqli_fetch_array($result, MYSQLI_ASSOC);

                $service_data = $this->getClientService($data['id']);

                $userData = array("id" => $data['user_id'], "user_name" => $data['user_name'], "email_id" => $data['email_id'], "phone" => $data['phone']);
                $data1 = array(
                    "id" => $data['id'],
                    "client_name" => $data['client_name'],
                    "email" => $data['email'],
                    "phone" => $data['phone'],
                    "address" => $data['address'],
                    "userData" => $userData,
                    "service_data" => $service_data,
                );
                $responseArray = array(
                    'clientData' => $data1,
                );
            }
            if ($responseArray) {
                $resultArray = array(
                    "apiStatus" => array(
                        "code" => "200",
                        "status" => "Ok",
                        "message" => "Client details fetched successfully",
                    ),
                    "result" => $responseArray,
                );
                return $resultArray;
            } else {
                return array(
                    "apiStatus" => array(
                        "code" => "404",
                        "message" => "No data found...",
                    ),
                );
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    //GetByIdClient function end

    /**
     * Post/Add sale
     *
     * @param array $data
     * @return multitype:string
     */

    /**
     * Post/Add sale
     *
     * @param array $data
     * @return multitype:string
     */

    //CreateClient function start
    private function createClient($data, $loginData)
    {
        // print_r($data);
        try {
            $db = $this->dbConnect();
            $validationData = array("client_name" => $data['client_name'], "email" => $data['email'], "phone" => $data['phone']);
            $this->validateInputDetails($validationData);

            $sql1 = "SELECT id FROM tbl_client WHERE client_name = '" . $data['client_name'] . "' AND email = '" . $data['email'] . "' AND phone = '" . $data['phone'] . "' AND status = 1";
            // print_r($sql1);exit;
            $result = mysqli_query($db, $sql1);
            $row_cnt = mysqli_num_rows($result);

            if ($row_cnt > 0) {
                throw new Exception("Client name & email_id is already exist");
            }
            // exit;
            if (!empty($data['client_name'])) {
                $client_name = $data['client_name'];
            } else {
                $client_name = "";
            }
            if (!empty($data['email'])) {
                $email = $data['email'];
            } else {
                $email = "";
            }
            if (!empty($data['phone'])) {
                $phone = $data['phone'];
            } else {
                $phone = "";
            }
            if (!empty($data['service_offer'])) {
                $service_offer = $data['service_offer'];
            } else {
                throw new Exception("Please validate the service offer");
                // $service_offer = [];
            }
            // $service_offer = $data['service_offer'];

            $dateNow = date("Y-m-d H:i:s");
            $insertQuery = "INSERT INTO tbl_client (client_name,email, phone,address,status, created_by, created_date) VALUES ('" . $client_name . "','" . $email . "','" . $phone . "','" . $data['address'] . "','" . '1' . "','" . $loginData['user_id'] . "','$dateNow')";
            $serviceIds = implode(',', $data['service_offer']);
            $serviceCheckQuery = "SELECT id FROM tbl_service_offered WHERE id IN ($serviceIds)";
            $serviceCheckResult = $db->query($serviceCheckQuery);
            // print_r($serviceCheckQuery);exit;

            if ($serviceCheckResult->num_rows !== count($data['service_offer'])) {
                throw new Exception("Service ID provided are invalid.");
            }

            if ($db->query($insertQuery) === true) {
                $lastInsertedId = mysqli_insert_id($db);
                $this->updateClientService($lastInsertedId, $service_offer);
                if (!empty($data['userData'])) {
                    $userConn = new USERMODEL();
                    $data['userData']['client_name'] = $data['client_name'];
                    $connect = $userConn->createUser($data, $loginData);

                    if ($connect['apiStatus']['code'] == 200) {
                        $userLastId = $connect['result']['lastUserId'];
                        $this->updateClientUser($lastInsertedId, $loginData, $userLastId);
                        // echo $userLastId;
                        $db->close();
                        $statusCode = "200";
                        $status = "Ok";
                        $statusMessage = "Client with User details created successfully";
                    } else {
                        $statusCode = $connect['apiStatus']['code'];
                        $status = "Ok";
                        $statusMessage = $connect['apiStatus']['message'];
                    }
                } else {
                    $statusCode = "200";
                    $status = "Ok";
                    $statusMessage = "Client details created successfully";
                }
            } else {
                $statusCode = "500";
                $statusMessage = "Unable to create Client details, please try again later";
            }

            $resultArray = array(
                "apiStatus" => array(
                    "code" => $statusCode,
                    "status" => $status,
                    "message" => $statusMessage),

            );
            return $resultArray;
        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                    "code" => "401",
                    "message" => $e->getMessage(),

                ));
        }
    }

    //CreateClient function end

    //Insert the client_id & service_id  to tbl_client_service_map
    private function updateClientService($lastInsertedId, $service_offer)
    {
        try {

            $db = $this->dbConnect();
            if ($lastInsertedId) {
                // for($x=0;$x<=count($service_offer);$x++){
                foreach ($service_offer as $x) {
                    $insertQuery = "INSERT INTO tbl_client_service_map (`client_id`, `service_id`, `created_by`) VALUES ('$lastInsertedId', $x, '$lastInsertedId') ";
                    $db->query($insertQuery);
                }
                $db->close();
                return true;
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

    //Insert the client_id & user_id  to tbl_client_user_map
    private function updateClientUser($lastInsertedId, $loginData, $userLastId)
    {
        try {

            $db = $this->dbConnect();
            if ($lastInsertedId) {
                $insertQuery = "INSERT INTO tbl_client_user_map (`client_id`, `user_id`, `created_by`) VALUES ('$lastInsertedId','$userLastId','" . $loginData['user_id'] . "') ";
                if ($db->query($insertQuery) === true) {
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

    //UpdateClient function start
    /**
     * Put/Update a Sale
     *
     * @param array $data
     * @return multitype:string
     */
    private function updateClient($data, $loginData)
    {
        try {
            $db = $this->dbConnect();
            $validationData = array("Id" => $data['id'], "client_name" => $data['client_name'], "email" => $data['email'], "phone" => $data['phone'], "address" => $data['address']);
            $this->validateInputDetails($validationData);
            // $userData = $data['userData'];

            $dateNow = date("Y-m-d H:i:s");
            $updateQuery = "UPDATE tbl_client SET client_name = '" . $data['client_name'] . "', email = '" . $data['email'] . "', phone = '" . $data['phone'] . "', address = '" . $data['address'] . "', updated_by = '" . $loginData['user_id'] . "', updated_date = '$dateNow' WHERE id = " . $data['id'];

            if ($db->query($updateQuery) === true) {
                $statusCode = "200";
                $statusMessage = "Client details updated successfully";
            } else {
                $statusCode = "500";
                $statusMessage = "Unable to update client details, please try again later";
            }
            $resultArray = array(
                "apiStatus" => array(
                    "code" => $statusCode,
                    "message" => $statusMessage,
                ),
            );
            return $resultArray;
        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                    "code" => "401",
                    "message" => $e->getMessage(),
                ),
            );
        }
    }
    //UpdateClient function end

    //DeleteClient function start

    private function deleteClient($data, $loginData)
    {
        try {
            $id = $data[2];
            $db = $this->dbConnect();
            if (empty($data[2])) {
                throw new Exception("Bad request id is required");
            }
            $sql = "SELECT id FROM tbl_client WHERE status = 1 and created_by = " . $loginData['user_id'] . " and id =$id";
            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);
            // echo $row_cnt;
            if ($row_cnt == 0) {
                throw new Exception("No data found...");
            }
            $deleteQuery = "UPDATE tbl_client set status=0 WHERE id = " . $id . "";
            if ($db->query($deleteQuery) === true) {
                $db->close();
                $statusCode = "200";
                $status = "Ok";
                $statusMessage = "Client details deleted successfully";
            } else {
                $deleteQuery = "UPDATE tbl_client AS cl, tbl_client_user_map AS cu, tbl_users AS u SET cl.status = 0,
            cu.status = 0,u.status = 0 WHERE cl.id = cu.client_id AND cu.user_id = u.id AND cl.id =" . $id . "";
                // echo $deleteQuery;exit;
                if ($db->query($deleteQuery) === true) {
                    $db->close();
                    $statusCode = "200";
                    $status = "Ok";
                    $statusMessage = "Client details deleted successfully";

                } else {
                    $statusCode = "500";
                    $statusMessage = "Unable to delete client details, please try again later";
                }
            }
            $resultArray = array(
                "apiStatus" => array(
                    "code" => $statusCode,
                    "status" => $status,
                    "message" => $statusMessage),

            );
            return $resultArray;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());

        }
    }
    //DeleteClient function end

    public function validateInputDetails($validationData)
    {
        foreach ($validationData as $key => $value) {
            if (empty($value) || trim($value) == "") {
                throw new Exception($key . " should not be empty!");
            }
        }
    }

    private function getTotalCount($loginData)
    {
        try {
            $db = $this->dbConnect();
            $sql = "SELECT * FROM tbl_client WHERE status = 1 and created_by = " . $loginData['user_id'] . "";
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
                    "message" => $e->getMessage(),

                ));
        }
    }
}
