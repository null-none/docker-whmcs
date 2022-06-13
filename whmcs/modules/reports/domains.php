<?php

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

$reportdata["title"] = "Domains";

$filterfields = array("id"=>"ID","userid"=>"User ID","clientname"=>"Client Name","orderid"=>"Order ID","type"=>"Reg Type","domain"=>"Domain Name","firstpaymentamount"=>"First Payment Amount","recurringamount"=>"Recurring Amount","registrationperiod"=>"Registration Period","registrationdate"=>"Registration Date","expirydate"=>"Expiry Date","nextduedate"=>"Next Due Date","registrar"=>"Registrar","paymentmethod"=>"Payment Method","status"=>"Status","additionalnotes"=>"Notes");

$reportdata["description"] = $reportdata["headertext"] = '';

$incfields = $whmcs->get_req_var('incfields');
$filterfield = $whmcs->get_req_var('filterfield');
$filtertype = $whmcs->get_req_var('filtertype');
$filterq = $whmcs->get_req_var('filterq');
if (!is_array($incfields)) $incfields = array();
if (!is_array($filterfield)) $filterfield = array();
if (!is_array($filtertype)) $filtertype = array();
if (!is_array($filterq)) $filterq = array();

if (!$print) {

    $reportdata["description"] = "This report can be used to generate a custom export of domains by applying up to 5 filters. CSV Export is available via the Tools menu to the right.";

    $reportdata["headertext"] = '<form method="post" action="reports.php?report='.$report.'">
<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
<tr><td width="20%" class="fieldlabel">Fields to Include</td><td class="fieldarea"><table width="100%"><tr>';
    $i=0;
    foreach ($filterfields AS $k=>$v) {
        $reportdata["headertext"] .= '<td width="20%"><input type="checkbox" name="incfields[]" value="'.$k.'" id="fd'.$k.'"';
        if (in_array($k,$incfields)) $reportdata["headertext"] .= ' checked';
        $reportdata["headertext"] .= ' /> <label for="fd'.$k.'">'.$v.'</label></td>'; $i++;
        if (($i%5)==0) $reportdata["headertext"] .= '</tr><tr>';
    }
    $reportdata["headertext"] .= '</tr></table></td></tr>';

    for ( $i = 1; $i <= 5; $i ++ ) {
        $reportdata["headertext"] .= '<tr><td width="20%" class="fieldlabel">Filter '.$i.'</td><td class="fieldarea"><select name="filterfield['.$i.']"><option value="">None</option>';
        foreach ($filterfields AS $k=>$v) {
            $reportdata["headertext"] .= '<option value="'.$k.'"';
            if (isset($filterfield[$i]) && $filterfield[$i]==$k) $reportdata["headertext"] .= ' selected';
            $reportdata["headertext"] .= '>'.$v.'</option>';
        }
        $reportdata["headertext"] .= '</select> <select name="filtertype['.$i.']">'
            . '<option value="=">Exact Match</option><option value="like"';
        if (isset($filtertype[$i]) && $filtertype[$i]=="like") $reportdata["headertext"] .= ' selected';
        $reportdata["headertext"] .= '>Containing</option></select> <input type="text" name="filterq['.$i.']" size="30" value="'.(isset($filterq[$i])?$filterq[$i]:'').'" /></td></tr>';
    }
    $reportdata["headertext"] .= '</table>
<p align="center"><input type="submit" value="Filter" /></p>
</form>';

}

if (count($incfields)) {
    $query = Capsule::table('tbldomains');

    foreach ($filterfield as $i => $val) {
        if ($val && array_key_exists($val, $filterfields)) {
            if ($filtertype[$i] == 'like') {
                $filterq[$i] = "%{$filterq[$i]}%";
            }
            if ($val == 'clientname') {
                $query->whereRaw(
                    "concat(tblclients.firstname, ' ', tblclients.lastname) "
                    . "{$filtertype[$i]} '{$filterq[$i]}'"
                );
            } else {
                $query->where(
                    "tbldomains.{$filterfield[$i]}",
                    $filtertype[$i],
                    $filterq[$i]
                );
            }
        }
    }

    foreach ($incfields as $fieldname) {
        if (array_key_exists($fieldname, $filterfields)) {
            $reportdata["tableheadings"][] = $filterfields[$fieldname];
            if ($fieldname=="clientname") {
                $query->addSelect(Capsule::raw("concat(tblclients.firstname, ' ', tblclients.lastname)"));
            } else {
                $query->addSelect("tbldomains.{$fieldname}");
            }
        }
    }

    $results = $query->join('tblclients', 'tblclients.id', '=', 'tbldomains.userid')->get();

    foreach ($results as $result) {
        $result = (array) $result;
        if (isset($result['registrar'])) {
            $result['registrar'] = ucfirst($result['registrar']);
        }
        if (isset($result['paymentmethod'])) {
            $result['paymentmethod'] = $gateways->getDisplayName($result['paymentmethod']);
        }
        $reportdata["tablevalues"][] = $result;
    }
}
