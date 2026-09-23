<?php

class CRM_Bemascontacts_Relationship {
  public static function writeBemasFunctionIntoMainAddress($contactId) {
    try {
      $concatenatedFunction = self::getConcatenatedMembershipTypeAndFunction($contactId);
      self::createOrupdateMainAddress($contactId, $concatenatedFunction);
    }
    catch (Exception $e) {
      watchdog('civi', $e->getMessage());
    }
  }

  private static function getConcatenatedMembershipTypeAndFunction($contactId) {
    $contact = self::getContactDetails($contactId);

    $typeOfMemberContact = self::getTypeOfMemberContact($contact);
    $bemasFunction = self::getBemasFunction($contact);
    $jobTitle = self::getJobTitle($contact);

    return $typeOfMemberContact . ' / ' . $bemasFunction . ' / ' . $jobTitle;
  }

  private static function getTypeOfMemberContact($contact) {
    if (empty($contact->relationship_type_id)) {
      return '';
    }

    if ($contact->is_active == 0) {
      return 'Mx';
    }

    if ($contact->relationship_type_id == 14) {
      return 'M1';
    }

    if ($contact->relationship_type_id == 15) {
      return 'Mc';
    }

    return '';
  }

  private static function getBemasFunction($contact) {
    return $contact->bemas_function;
  }

  private static function getJobTitle($contact) {
    return $contact->job_title;
  }

  private static function getContactDetails($contactId) {
    $sql = "
      SELECT
        trim(IFNULL(i.`function_28`, '')) bemas_function,
        trim(IFNULL(c.job_title, '')) job_title,
        r.is_active,
        r.relationship_type_id
      FROM
        civicrm_contact c
      INNER JOIN
        `civicrm_value_individual_details_19` i ON c.id = i.`entity_id`
      LEFT OUTER JOIN
        civicrm_relationship r on r.contact_id_a = c.id and r.relationship_type_id in (14, 15)
      WHERE
        c.id = $contactId
      and
        c.contact_type = 'Individual'
      ORDER BY
        r.is_active desc, r.relationship_type_id asc
    ";

    $dao = CRM_Core_DAO::executeQuery($sql);
    if ($dao->fetch()) {
      return $dao;
    }
    else {
      throw new Exception("Contact $contactId not found");
    }
  }

  private static function createOrupdateMainAddress($contactId, $concatenatedFunction) {
    $params = [
      'contact_id' => $contactId,
      'location_type_id' => 3,
      'is_primary' => 1,
      'is_billing' => 0,
      'postal_code' => '',
      'street_address' => '',
      'street_name' => '',
      'city' => mb_substr($concatenatedFunction, 0, 64),
    ];

    if (self::updateMainAddress($params) == FALSE) {
      self::createMainAddress($params);
    }
  }

  private static function updateMainAddress($params) {
    $address = civicrm_api3('Address', 'get', [
      'contact_id' => $params['contact_id'],
      'location_type_id' => $params['location_type_id'],
      'sequential' => 1,
    ]);

    if ($address['count'] == 0) {
      return FALSE;
    }
    else {
      if ($address['values'][0]['city'] != $params['city']) {
        $params['id'] = $address['values'][0]['id'];
        civicrm_api3('Address', 'create', $params);
      }

      return TRUE;
    }
  }

  private static function createMainAddress($params) {
    civicrm_api3('Address', 'create', $params);
  }
}
