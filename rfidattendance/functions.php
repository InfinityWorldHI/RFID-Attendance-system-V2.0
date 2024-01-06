<?php

interface ObjectInterface
{
    public function parse(array $array);

    public function save();

    public function getData(): array;
}

class DatabaseObject implements ObjectInterface
{
    
    public $id = 0;
    const TABLE_NAME = "table";
    const TABLE_ID = "id";
    /**
     * database connection
     * @var mysqli
     */
    protected $conn = null;
    /**
     * Creates a new Database Object
     * @param array $db_data to be parsed
     * @throws Exception if content is not json
     */
    public function __construct($db_data = [], mysqli $conn = null)
    {
        $this->conn = $conn;
        if (is_array($db_data)) {
            $this->parse($db_data);
        }
    }

    /**
     * Parsing the terminal message as json
     * @param array $array
     * @throws Exception if content is not json
     * @return void
     */
    public function parse(array $array)
    {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                } else {
                    echo $key . " not allowed in " . get_class($this) . "-Message";
                    throw new Exception($key . " not allowed in " . get_class($this) . "-Message");
                }
            }
        } else {
            echo "no values for parsing " . get_class($this) . "-Message";
            throw new Exception("no values for parsing " . get_class($this) . "-Message");
        }
    }


    public function getData(bool $insert = false): array
    {
        $array = [];
        foreach (get_object_vars($this) as $key => $value) {
            if (($key != "conn" && !$insert) || ($insert && !in_array($key, [static::TABLE_ID, "conn"])))
                $array[$key] = $value;
        }
        return $array;
    }

    private function getDataToSave(): array
    {
        $array = [];
        foreach (get_object_vars($this) as $key => $value) {
            if ($key != "conn" && $key != static::TABLE_ID) {
                $className = get_class($this);
                $templateObject = new $className();
                switch (gettype($templateObject->$key)) {
                    case "boolean":
                    case "integer":
                        $array[] = $key . " = '" . (int)$value . "'";
                        break;
                    case "string":
                        $array[] = $key . " = '" . (string)$value . "'";
                        break;
                    default:
                        $array[] = $key . " = '" . $value . "'";
                }
            }
        }
        return $array;
    }

    public function save()
    {
        $sql = "UPDATE " . static::TABLE_NAME . " SET " . implode(",", $this->getDataToSave()) . " WHERE " . static::TABLE_ID . " = '" . $this->id . "';";
        if ($this->conn->query($sql) === TRUE) {
            return true;
        }
        return false;
    }

    public function insert()
    {
        $data_to_save = $this->getData(true);
        $sql = "INSERT INTO " . static::TABLE_NAME . " (`" . implode("`,`", array_keys($data_to_save)) . "`) 
                VALUES ('" . implode("','", array_values($data_to_save)) . "')";
        if ($this->conn->query($sql)) {
            return true;
        }
        return false;
    }
}

class DeviceObject extends DatabaseObject
{
    const TABLE_NAME = "devices";
    const TABLE_ID = "id";

    const DEVICE_MODE_TIME = 1;
    const DEVICE_MODE_LEARN = 0;

    public $device_name = "";
    public $device_dep = "";
    public $device_uid = "";
    public $device_date = null;
    public $device_mode = false;
}

class UserObject extends DatabaseObject
{
    const TABLE_NAME = "users";
    const TABLE_ID = "id";
    public $username = "";
    public $serialnumber = 0;
    public $gender = "";
    public $email = "";
    public $card_uid = "";
    public $card_select = 0;
    public $user_date = null;
    public $calendarId = "";
    public $device_uid = "";
    public $device_dep = "";
    public $add_card = 0;
}

class UserLogObject extends DatabaseObject
{
    const TABLE_NAME = "users_logs";
    const TABLE_ID = "id";
    public $username = "";
    public $serialnumber = 0;
    public $card_uid = "";
    public $card_out = 0;
    public $device_uid = "";
    public $device_dep = "";
    public $checkindate = null;
    public $timein = null;
    public $timeout = null;
    public $calendarEventId = "";
}

class AdminObject extends DatabaseObject
{
    const TABLE_NAME = "admin";
    const TABLE_ID = "id";
    public $admin_name = "";
    public $admin_email = "@example.com";
    public $admin_pwd = "";
    public $admin_passwd_reset_token = "";
    public $admin_passwd_reset_timeout = "";
}

function getDeviceByToken(string $device_uid = ""): ?DeviceObject
{
    global $conn;
    $result = $conn->query("SELECT * FROM " . DeviceObject::TABLE_NAME . " WHERE device_uid = '$device_uid'");
    if ($result->num_rows == 1) {
        return new DeviceObject($result->fetch_assoc(), $conn);
    }
    return null;
}

function getDeviceById(int $id = 0): ?DeviceObject
{
    global $conn;
    $result = $conn->query("SELECT * FROM " . DeviceObject::TABLE_NAME . " WHERE " . DeviceObject::TABLE_ID . " = '$id'");
    if ($result->num_rows == 1) {
        return new DeviceObject($result->fetch_assoc(), $conn);
    }
    return null;
}

/**
 * @return DeviceObject[]
 */
function getAllDevices(): array
{
    global $conn;
    $result = $conn->query("SELECT * FROM " . DeviceObject::TABLE_NAME . " ORDER BY " . DeviceObject::TABLE_ID . " ASC");
    $return = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $return[] =  new DeviceObject($row, $conn);
        }
        return $return;
    }
    return [];
}

function deleteDevice(int $id = 0): bool
{
    global $conn;
    return ($conn->query("DELETE FROM " . DeviceObject::TABLE_NAME . " WHERE " . DeviceObject::TABLE_ID . " = '$id'") === TRUE);
}
#
/**
 * @return array
 */
function getAllDepartments(): array
{
    global $conn;
    $result = $conn->query("SELECT device_dep FROM " . DeviceObject::TABLE_NAME . "  GROUP BY device_dep ORDER BY device_dep ASC");
    return array_column($result->fetch_all(MYSQLI_ASSOC), "device_dep");
}

function getUserByCardId(string $card_id = ""): ?UserObject
{
    global $conn;
    $result = $conn->query("SELECT * FROM " . UserObject::TABLE_NAME . " WHERE card_uid = '$card_id'");
    if ($result->num_rows == 1) {
        return new UserObject($result->fetch_assoc(), $conn);
    }
    return null;
}

function getUserById(int $id = 0): ?UserObject
{
    global $conn;
    $result = $conn->query("SELECT * FROM " . UserObject::TABLE_NAME . " WHERE " . UserObject::TABLE_ID . " = '$id'");
    if ($result->num_rows == 1) {
        return new UserObject($result->fetch_assoc(), $conn);
    }
    return null;
}

function deleteUser(int $id = 0): bool
{
    global $conn;
    return ($conn->query("DELETE FROM " . UserObject::TABLE_NAME . " WHERE " . UserObject::TABLE_ID . " = '$id'") === TRUE);
}

/**
 * @return UserObject[]
 */
function getAllActiveUsers(): array
{
    global $conn;
    $result = $conn->query("SELECT * FROM " . UserObject::TABLE_NAME . " WHERE add_card = 1 OR card_select = 1 ORDER BY " . UserObject::TABLE_ID . " DESC");
    $return = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $return[] =  new UserObject($row, $conn);
        }
        return $return;
    }
    return [];
}

function unselectUsers(): bool
{
    global $conn;
    return ($conn->query("UPDATE " . UserObject::TABLE_NAME . " SET card_select = 0 WHERE card_select  = 1") === TRUE);
}

function selectUserByCardId(string $card_id = ""): ?UserObject
{
    global $conn;
    unselectUsers();
    $result = $conn->query("SELECT * FROM " . UserObject::TABLE_NAME . " WHERE card_uid = '$card_id'");
    if ($result->num_rows == 1) {
        $user = new UserObject($result->fetch_assoc(), $conn);
        $user->card_select = 1;
        if ($user->save()) {
            return $user;
        }
    }
    return null;
}

function getLogByCheckinDate(string $date = "", string $card_uid = ""): ?UserLogObject
{
    global $conn;
    $result = $conn->query("SELECT * FROM " . UserLogObject::TABLE_NAME . " WHERE card_uid='" . $card_uid . "' AND checkindate='" . $date . "' AND card_out=0");
    if ($result->num_rows >= 1) {
        return new UserLogObject($result->fetch_assoc(), $conn);
    }
    return null;
}

/**
 * @return UserLogObject[]
 */
function getLogList(string $search = ''): array
{
    global $conn;

    $result = $conn->query("SELECT * FROM " . UserLogObject::TABLE_NAME . " WHERE " . $search . " ORDER BY " . UserLogObject::TABLE_ID . " DESC");
    $return = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $return[] =  new UserLogObject($row, $conn);
        }
    }
    return $return;
}

function getAdminBy(int $id = 0): ?AdminObject
{
    global $conn;
    $result = $conn->query("SELECT * FROM " . AdminObject::TABLE_NAME . " WHERE " . AdminObject::TABLE_ID . " = '$id'");
    if ($result->num_rows == 1) {
        return new AdminObject($result->fetch_assoc(), $conn);
    }
    return null;
}
function getAdminByEmail(string $email = ""): ?AdminObject
{
    global $conn;
    $result = $conn->query("SELECT * FROM " . AdminObject::TABLE_NAME . " WHERE admin_email = '$email'");
    if ($result->num_rows == 1) {
        return new AdminObject($result->fetch_assoc(), $conn);
    }
    return null;
}

function getAdminByToken(string $token = ""): ?AdminObject
{
    global $conn;
    $result = $conn->query("SELECT * FROM " . AdminObject::TABLE_NAME . " WHERE admin_passwd_reset_token = '$token'");
    if ($result->num_rows == 1) {
        return new AdminObject($result->fetch_assoc(), $conn);
    }
    return null;
}
