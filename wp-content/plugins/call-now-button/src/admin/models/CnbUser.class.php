<?php

namespace cnb\admin\models;

class Cnb_User {
    /**
     * @var string UUID of the User
     */
    public $id;

    /**
     * @var string Name of the User
     */
    public $name;

    /**
     * Usually the same as admin_email
     *
     * @var string email address of the User
     */
    public $email;

    /**
     * @var string
     */
    public $companyName;

    /**
     * @var Cnb_User_Address
     */
    public $address;

    /**
     * @var array{Cnb_user_TaxId}
     */
    public $taxIds;
}

class Cnb_user_TaxId {
    public $value;
    /**
     * @var Cnb_user_TaxId_Verification
     */
    public $verification;
}

class Cnb_user_TaxId_Verification {
    /**
     * @var string either "verified" or "pending"
     */
    public $status;
}

class Cnb_User_Address {
    public $line1;
    public $line2;
    public $postalCode;
    public $city;
    public $state;
    public $country;

}