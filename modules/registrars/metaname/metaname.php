<?php

use WHMCS\Module\Registrar\Metaname\Metaname;
use WHMCS\Module\Registrar\Metaname\Exception\JsonRpcFault;

function metaname_getConfigArray()
{
    return array(
        'AccountRef' => array(
            'FriendlyName' => 'Account Reference',
            'Description' => 'Enter your Metaname Account Reference here.',
            'Size' => '4',
            'Type' => 'text'
       ),
        'APIKey' => array(
            'FriendlyName' => 'API Key',
            'Description' => 'Enter your Metaname API Key here.',
            'Size' => '40',
            'Type' => 'text'
       ),
            'TestSite' => array(
            'FriendlyName' => 'Test Site',
            'Description' => 'Tick to use the Metaname Test Site.',
            'Type' => 'yesno'
       )
   );
}

function metaname_RegisterDomain(array $params)
{
    $metaname = new Metaname($params);
    $domain_name = $metaname->specified_domain_name();
    $term_in_months = $metaname->specified_term_in_months();
    $contacts = $metaname->specified_contacts();
    $name_servers = $metaname->specified_name_servers();
    try {
        $udai = $metaname->register_domain_name($domain_name, $term_in_months, $contacts, $name_servers);
        $message = "$domain_name has been registered for $term_in_months months";
        if ($udai != null) {
            $message .= ".  Its UDAI is $udai";
        }
        return array('info' => $message);
    } catch (JsonRpcFault $e) {
        return $metaname->handle_fault($e, array(-4, -6, -7, -8, -9, -11, -14,));
    }
}

function metaname_TransferDomain(array $params)
{
    $metaname = new Metaname($params);
    $domain_name = $metaname->specified_domain_name();
    try {
        if ($metaname->nz_name_specified()) {
          $udai = $params['eppcode'];
          $new_udai = $metaname->import_nz_domain_name($domain_name, $udai);
          return array('info' => "$domain_name has been transferred to your account.  Its new UDAI is $new_udai");
        } else {
          $metaname->import_other_domain_name($domain_name, $metaname->specified_contacts());
        }
    } catch (JsonRpcFault $e) {
        return $metaname->handle_fault($e, array(-4, -6, -8, -15,));
    }
}

function metaname_RenewDomain(array $params)
{
    $metaname = new Metaname($params);
    $domain_name = $metaname->specified_domain_name();
    $term_in_months = $metaname->specified_term_in_months();
    try {
        $metaname->renew_domain_name($domain_name, $term_in_months);
        return array('info' => "$domain_name has been renewed for $term_in_months months");
    } catch (JsonRpcFault $e) {
        return $metaname->handle_fault($e, array(-4, -5, -11,));
    }
}

function metaname_GetNameservers(array $params)
{
  $metaname = new Metaname($params);
  $domains = $metaname->domain_names();
  $domain = $metaname->domain_named($metaname->specified_domain_name(), $domains);
  if ($domain != null) {
      $values = array();
      for ($i=0, $n=1; $n <= count($domain->name_servers); $i+=1, $n+=1)
      {
          $values['ns' . $n] = $domain->name_servers[$i]->name;
      }
      return $values;
  } else {
      return array('error' => 'This domain does not appear to be in your portfolio');
  }
}

function metaname_SaveNameservers(array $params)
{
    $metaname = new Metaname($params);
    try {
        $metaname->update_name_servers($metaname->specified_domain_name(), $metaname->specified_name_servers());
        return null;
    } catch (JsonRpcFault $e) {
        return $metaname->handle_fault($e, array(-4, -5, -9, -13));
    }
}

function metaname_GetContactDetails(array $params)
{
    $metaname = new Metaname($params);
    $domains = $metaname->domain_names();
    $domain = $metaname->domain_named($metaname->specified_domain_name(), $domains);
    if ($domain != null) {
        $values = array();
        $metaname->copy_contact_details($domain->contacts->registrant, $values, 'Registrant');
        $metaname->copy_contact_details($domain->contacts->admin, $values, 'Admin');
        $metaname->copy_contact_details($domain->contacts->technical,  $values, 'Tech');
        return $values;
    } else {
        return array('error' => 'This domain does not appear to be in your portfolio');
    }
}

function metaname_SaveContactDetails(array $params)
{
    $metaname = new Metaname($params);
    $contacts = array(
        'registrant' => $metaname->encoded_contact('Registrant'),
        'admin' => $metaname->encoded_contact('Admin'),
        'technical'  => $metaname->encoded_contact('Tech'),
    );
    try {
        $metaname->update_contacts($metaname->specified_domain_name(), $contacts);
    } catch (JsonRpcFault $e) {
        return $metaname->handle_fault($e, array(-4, -5, -6, -8,));
    }
}

function metaname_GetRegistrarLock(array $params)
{
    $metaname = new Metaname($params);
    try {
        return $metaname->domain_name_is_locked($metaname->specified_domain_name()) ? 'locked' : 'unlocked';
    } catch (JsonRpcFault $e) {
        # Errors -4, -5 and -18 are also documented for domain_name_is_locked
        # although any of these would be a WHMCS system error since this method
        # should be invoked only for domain names in the reseller's portfolio
        return $metaname->handle_fault($e, array(-19));
    }
}

function metaname_SaveRegistrarLock(array $params)
{
    $metaname = new Metaname($params);
    try {
        $domain_name = $metaname->specified_domain_name();
        if ($params['lockenabled']) {
            return $metaname->lock_domain_name($domain_name);
        } else {
            return $metaname->unlock_domain_name($domain_name);
        }
    } catch (JsonRpcFault $e) {
        # Errors -4 is also documented for lock_domain_name and unlock_domain_name
        # although any of these would be a WHMCS system error since this method
        # should be invoked only for domain names in the reseller's portfolio.
        # Error -18 is a system error anyway and is translated to the generic
        # system error
        return $metaname->handle_fault($e, array(-5, -19));
    }
}

function metaname_GetDNS(array $params)
{
    $metaname = new Metaname($params);
    try {
        $records = $metaname->dns_zone($metaname->specified_domain_name());
        # Convert the RRs from Metaname form to WHMCS form
        for ($i = 0; $i < count($records); $i += 1)
        {
            $record = $records[$i];
            $records[$i] = array(
                'hostname' => $record->name,
                'type' => $record->type,
                'address' =>  $record->data
           );
        }
        return $records;
    } catch (JsonRpcFault $e) {
        return $metaname->handle_fault($e, array(-5,-12));
    }
}

function metaname_SaveDNS(array $params)
{
    $metaname = new Metaname($params);
    $metaname->inspect('metaname_SaveDNS', $params);
    try {
        $records = array();
        # Convert $records from WHMCS form to Metaname form
        foreach ($params['dnsrecords'] as $key => $record)
        {
            $metaname->inspect('rc', $record);
            $records[] = array(
                'name' => $record['hostname'],
                'type' => $record['type'],
                'aux' => ('MX' == $record['type'] ? 0 : null),
                'ttl' => 3600,
                'data' => $record['address']
           );
        }
        $metaname->configure_zone($metaname->specified_domain_name(), $records, null);
        return null;
    } catch (JsonRpcFault $e) {
        return $metaname->handle_fault($e, array(-5, -12, -16, -17));
    }
}

function metaname_GetEPPCode(array $params)
{
    $metaname = new Metaname($params);
    try {
        $udai = $metaname->reset_domain_name_secret($metaname->specified_domain_name());
        if ($udai) {
            return array('eppcode' => $udai);
        }
        # Otherwise, no value is returned and WHMCS assumes that the EPP code has
        # been e-mailed to the Admin contact
    } catch (JsonRpcFault $e) {
        return $metaname->handle_fault($e, array(-5));
    }
}

function metaname_Sync(array $params, $transfer = false)
{
    $metaname = new Metaname($params);
    try {
        $params = injectDomainObjectIfNecessary($params);
        $domainInformation = $metaname->domain_name($params['domainObj']->getDomain());
        $status = $domainInformation->status;
        if ($status == 'Transferring' && $transfer) {
            return [
                'inprogress' => true,
            ];
        }
        $expiryDate = $domainInformation->when_paid_up_to;
        $expiryDate = explode('T', $expiryDate)[0];
        $returnData = [];
        $returnData['expirydate'] = $expiryDate;
        if (in_array($status, ['Active', 'Locked'])) {
            $returnData['active'] = true;
        } elseif ($status == 'PendingRelease') {
            $returnData['expired'] = true;
        }
        return $returnData;
    } catch (JsonRpcFault $e) {
        if ($e->getCode() == -5) {
            $key = 'expired';
            if ($transfer) {
                $key = 'transferredAway';
            }
            return [
                $key => true,
            ];
        }
        return $metaname->handle_fault($e, array(-4));
    }
}

function metaname_TransferSync(array $params)
{
    return metaname_Sync($params, true);
}
