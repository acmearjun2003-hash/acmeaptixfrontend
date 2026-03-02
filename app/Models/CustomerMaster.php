<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerMaster extends Model
{
    protected $table = 'customermaster';

    protected $primaryKey = 'OWNCODE';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'OWNCODE',
        'NAME',
        'ADDRESS',
        'CITYCODE',
        'PACKAGETYPE',
        'PACKAGESUBTYPE',
        'CUSTCODE',
        'CONTACTNUMBER',
        'OWNERNAME1',
        'OWNERNAME2',
        'OWNERNAME3',
        'MOBILENUMBER1',
        'MOBILENUMBER2',
        'MOBILENUMBER3',
        'NOOFBRANCHES',
        'CUSTOMERTYPE',
        'STDCODE',
        'EMAILADDRESS1',
        'EMAILADDRESS2',
        'EMAILADDRESS3',
        'PANNUMBER',
        'WEBSITE',
        'CANCELFLAG',
        'ISLOCKED',
        'StdCode1',
        'StdCode2',
        'PhoneNumber1',
        'PhoneNumber2',
        'OStdCode1',
        'OStdCode2',
        'OStdCode3',
        'OPhoneNumber1',
        'OPhoneNumber2',
        'OPhoneNumber3',
        'IsBTUpdated',
        'customer_number',
        'PortalID',
        'TypeOfCustomer',
        'WebCustomerCode',
        'InstallationCode',
    ];

    protected $casts = [
        'OWNCODE' => 'integer',
        'CITYCODE' => 'integer',
        'PACKAGETYPE' => 'integer',
        'PACKAGESUBTYPE' => 'integer',
        'CUSTCODE' => 'integer',
        'NOOFBRANCHES' => 'integer',
        'CUSTOMERTYPE' => 'integer',
        'CANCELFLAG' => 'integer',
        'ISLOCKED' => 'integer',
        'PortalID' => 'integer',
        'TypeOfCustomer' => 'integer',
        'WebCustomerCode' => 'integer',
    ];
}

