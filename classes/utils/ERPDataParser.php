<?php
/**
 * For mapping data from QB to local
 */
final class ERPDataParser {

    /**
     * Return an array in order: created time and last updated time
     */
    public static function parseMetaTime($metaData) {
        return [
            date('Y-m-d H:i:s', strtotime($metaData->CreateTime)),
            date('Y-m-d H:i:s', strtotime($metaData->LastUpdatedTime))
        ];
    }

    public static function parseAttachment($entity) {
        list($created_at, $last_updated_at) = self::parseMetaTime($entity->MetaData);
        if (null != $entity->AttachableRef) {
            $estimate_id = $entity->AttachableRef->EntityRef;
        } else {
            $estimate_id = null;
        }
        return [
            'id'                => $entity->Id,
            'sync_token'        => $entity->SyncToken,
            'estimate_id'       => $estimate_id,
            'size'              => $entity->Size,
            'content_type'      => $entity->ContentType,
            'access_uri'        => $entity->FileAccessUri,
            'tmp_download_uri'  => $entity->TempDownloadUri,
            'file_name'         => $entity->FileName,
            'created_at'        => $created_at,
            'last_updated_at'   => $last_updated_at,
        ];
    }

    public function parseProductService($entity) {
        $active = $entity->Active == 'true';
        $taxable = $entity->Taxable == 'true';
        list($created_at, $last_updated_at) = self::parseMetaTime($entity->MetaData);
        return [
            'id'                => $entity->Id,
            'sync_token'        => $entity->SyncToken,
            'name'              => $entity->Name,
            'description'       => $entity->Description,
            'rate'              => $entity->UnitPrice,
            'active'            => $active,
            'taxable'           => $taxable,
            'created_at'        => $created_at,
            'last_updated_at'   => $last_updated_at,
        ];
    }

    public function parseEstimateLine($entity, $estimateId) {
        if ($entity->Id) {
            $qty = $rate = $product_service_id = null;
            switch ($entity->DetailType) {
                case 'SalesItemLineDetail':
                    $saleItem = $entity->SalesItemLineDetail;
                    if ($saleItem->Qty) {
                        $qty = $saleItem->Qty;
                    }
                    if ($saleItem->UnitPrice) {
                        $rate = $saleItem->UnitPrice;
                    }
                    $product_service_id = $saleItem->ItemRef;
                    break;
                case 'DescriptionOnly':
                    break;
                case 'DiscountLineDetail':
                    break;
                case 'SubTotalLineDetail':
                    break;
            }

            return [
                'line_id'       => $entity->Id,
                'line_num'      => $entity->LineNum,
                'estimate_id'   => $estimateId,
                'product_service_id' => $product_service_id,
                'qty'           => $qty,
                'rate'          => $rate,
                'description'   => $entity->Description,
            ];
        }
    }

    public function parseCustomer($entity) {
        // Parse billing address
        $billAddress = $entity->BillAddr;
        $bill_address_id
            = $bill_address
            = $bill_city
            = $bill_state
            = $bill_zip_code
            = $bill_country
            = null;
        if (null != $billAddress) {
            $bill_address_id = $billAddress->Id;
            $bill_address = $billAddress->Line1;
            $bill_city = $billAddress->City;
            $bill_state = $billAddress->CountrySubDivisionCode;
            $bill_zip_code = $billAddress->PostalCode;
            $bill_country = $billAddress->Country;
        }
        // Parse shipping address
        $shipAddr = $entity->ShipAddr;
        $shipAddressId
            = $shipAddress
            = $shipCity
            = $shipState
            = $shipZipCode
            = $shipCountry
            = null;
        if (null != $shipAddr) {
            $shipAddressId = $shipAddr->Id;
            $shipAddress = $shipAddr->Line1;
            $shipCity = $shipAddr->City;
            $shipState = $shipAddr->CountrySubDivisionCode;
            $shipZipCode = $shipAddr->PostalCode;
            $shipCountry = $shipAddr->Country;
        }

        $primary_phone_number
            = $mobile_phone_number
            = $alternate_phone_number
            = $fax
            = $email
            = $parentId
            = null;
        if (null != $entity->PrimaryPhone) {
            $primary_phone_number = $entity->PrimaryPhone->FreeFormNumber;
        }
        if (null != $entity->Mobile) {
            $mobile_phone_number = $entity->Mobile->FreeFormNumber;
        }
        if (null != $entity->AlternatePhone) {
            $alternate_phone_number = $entity->AlternatePhone->FreeFormNumber;
        }
        if (null != $entity->Fax) {
            $fax = $entity->Fax->FreeFormNumber;
        }
        if (null != $entity->PrimaryEmailAddr) {
            $email = $entity->PrimaryEmailAddr->Address;
        }
        $subLevel = 0;
        if (null != $entity->Level) {
            $subLevel = $entity->Level;
        }
        list($created_at, $last_updated_at) = self::parseMetaTime($entity->MetaData);
        $active = $entity->Active == 'true';
        $parentId = $entity->ParentRef;
        return [
            'id'                => $entity->Id,
            'sync_token'        => $entity->SyncToken,
            'parent_id'         => $parentId,
            'sub_level'         => $subLevel,
            'title'             => $entity->Title,
            'given_name'        => $entity->GivenName,
            'middle_name'       => $entity->MiddleName,
            'family_name'       => $entity->FamilyName,
            'suffix'            => $entity->Suffix,
            'display_name'      => $entity->DisplayName,
            'print_name'        => $entity->PrintOnCheckName,
            'company_name'      => $entity->CompanyName,
            'email'             => $email,
            'primary_phone_number'      => $primary_phone_number,
            'mobile_phone_number'       => $mobile_phone_number,
            'alternate_phone_number'    => $alternate_phone_number,
            'fax'                       => $fax,

            'bill_address_id'   => $bill_address_id,
            'bill_address'      => $bill_address,
            'bill_city'         => $bill_city,
            'bill_state'        => $bill_state,
            'bill_zip_code'     => $bill_zip_code,
            'bill_country'      => $bill_country,

            'ship_address_id'   => $shipAddressId,
            'ship_address'      => $shipAddress,
            'ship_city'         => $shipCity,
            'ship_state'        => $shipState,
            'ship_zip_code'     => $shipZipCode,
            'ship_country'      => $shipCountry,

            'active'            => $active,
            'created_at'        => $created_at,
            'last_updated_at'   => $last_updated_at,
        ];
    }

    public function parseEmployee($entity) {
        $primary_address
            = $primary_city
            = $primary_state
            = $primary_zip_code
            = $primary_country
            = null;
        $primaryAddress = $entity->PrimaryAddr;
        if (null != $primaryAddress) {
            $primary_address = $primaryAddress->Line1;
            $primary_city = $primaryAddress->City;
            $primary_state = $primaryAddress->CountrySubDivisionCode;
            $primary_zip_code = $primaryAddress->PostalCode;
            $primary_country = $primaryAddress->Country;
        }
        $primary_phone_number = $email = null;
        if (null != $entity->PrimaryPhone) {
            $primary_phone_number = $entity->PrimaryPhone->FreeFormNumber;
        }
        if (null != $entity->PrimaryEmailAddr) {
            $email = $entity->PrimaryEmailAddr->Address;
        }

        list($created_at, $last_updated_at) = self::parseMetaTime($entity->MetaData);
        $active = $entity->Active == 'true';

        return [
            'id'                => $entity->Id,
            'sync_token'        => $entity->SyncToken,
            'primary_address'   => $primary_address,
            'primary_city'      => $primary_city,
            'primary_state'     => $primary_state,
            'primary_zip_code'  => $primary_zip_code,
            'primary_country'   => $primary_country,
            'given_name'        => $entity->GivenName,
            'middle_name'       => $entity->MiddleName,
            'family_name'       => $entity->FamilyName,
            'suffix'            => $entity->Suffix,
            'display_name'      => $entity->DisplayName,
            'print_name'        => $entity->PrintOnCheckName,
            'email'             => $email,
            'primary_phone_number' => $primary_phone_number,
            'ssn'               => $entity->SSN,
            'active'            => $active,
            'created_at'        => $created_at,
            'last_updated_at'   => $last_updated_at,
        ];
    }

    public function parseEstimate($data, $data_local = null) {
        $txn_date = $expiration_date = $due_date = null;
        if ($data->TxnDate) {
            $txn_date = $data->TxnDate;
        }
        $expiration_date = $data_local['expiration_date'];
        if ($data->ExpirationDate) {
            $expiration_date = date('Y-m-d H:i:s', strtotime($data->ExpirationDate));
        }
        if ($data->DueDate) {
            $due_date = date('Y-m-d H:i:s', strtotime($data->DueDate));
        }
        $billAddress = $data->BillAddr;
        $bill_address_id
            = $bill_address
            = $bill_city
            = $bill_state
            = $bill_zip_code
            = null;
        if (null != $billAddress) {
            $bill_address_id = $billAddress->Id;
            $bill_address = $billAddress->Line1;
            $bill_city = $billAddress->City;
            $bill_state = $billAddress->CountrySubDivisionCode;
            $bill_zip_code = $billAddress->PostalCode;
        }

        $shipAddress = $data->ShipAddr;
        $job_address_id
            = $job_address
            = $job_city
            = $job_state
            = $job_zip_code
            = null;
        if (null != $shipAddress) {
            $job_address_id = $shipAddress->Id;
            $job_address = $shipAddress->Line1;
            $job_city = $shipAddress->City;
            $job_state = $shipAddress->CountrySubDivisionCode;
            $job_zip_code = $shipAddress->PostalCode;
        }

        $email = null;
        if (null != ($data->BillEmail)) {
            $email = $data->BillEmail->Address;
        }
        $estimate_footer = $data->CustomerMemo;
        list($created_at, $last_updated_at) = self::parseMetaTime($data->MetaData);
        if (($data_local != null) && ($data->TxnStatus == 'Accepted') &&
            ($data_local['status'] == 'Completed')) {
            $status = 'Completed';
        } else {
            $status = $data->TxnStatus;
        }
        if ($data_local['job_customer_id']) {
            $job_customer_id = $data_local['job_customer_id'];
        } else {
            $job_customer_id = $data->CustomerRef;
        }
        $sold_by_1 = $sold_by_2 = null;
        try {
            if (null != $data->CustomField && is_array($data->CustomField)) {
                foreach ($data->CustomField as $customField) {
                    if ($customField->DefinitionId == 2) {
                        $sold_by_1 = $customField->StringValue;
                    }
                    if ($customField->DefinitionId == 3) {
                        $sold_by_2 = $customField->StringValue;
                    }
                }
            }
        } catch(Exception $e) {}
        return [
            'id' => $data->Id,
            'customer_id' => $data->CustomerRef,
            'sync_token' => $data->SyncToken,
            'doc_number' => $data->DocNumber,
            'estimate_footer' => $estimate_footer,
            'txn_date' => $txn_date,
            'expiration_date' => $expiration_date,
            'due_date' => $due_date,
            'email' => $email,

            'bill_address_id' => $bill_address_id,
            'bill_address' => $bill_address,
            'bill_city' => $bill_city,
            'bill_state' => $bill_state,
            'bill_zip_code' => $bill_zip_code,
            'bill_country' => $data_local['bill_country'],

            'status' => $status,
            'created_at' => $created_at,
            'last_updated_at' => $last_updated_at,
            'total' => $data->TotalAmt,
            'primary_phone_number' => $data_local['primary_phone_number'],
            'alternate_phone_number' => $data_local['alternate_phone_number'],
            'route_id' => $data_local['route_id'],
            'source' => $data_local['source'],
            'customer_signature' => $data_local['customer_signature'],
            'location_notes' => $data_local['location_notes'],
            'date_of_signature' => $data_local['date_of_signature'],
            'sold_by_1' => $sold_by_1,
            'sold_by_2' => $sold_by_2,

            'job_customer_id'   => $job_customer_id,
            'job_address_id'    => $job_address_id,
            'job_address'       => $job_address,
            'job_city'          => $job_city,
            'job_state'         => $job_state,
            'job_zip_code'      => $job_zip_code,
            'job_country'       => $data_local['job_country'],

            'job_lat' => $data_local['job_lat'],
            'job_lng' => $data_local['job_lng'],
        ];
    }
}
?>
