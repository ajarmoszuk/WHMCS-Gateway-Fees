<?php

function update_gateway_fee3($vars)
{
    $id = $vars['invoiceid'];
    updateInvoiceTotal($id);
}

function update_gateway_fee1($vars)
{
    $id = $vars['invoiceid'];
    $result = select_query("tblinvoices", '', "id='" . $id . "'", "", "");
    $data = mysql_fetch_array($result);
    update_gateway_fee2(array(
        'paymentmethod' => $data['paymentmethod'],
        "invoiceid" => $data['id']
    ));
}

function update_gateway_fee2($vars)
{
    $paymentmethod = $vars['paymentmethod'];
    delete_query("tblinvoiceitems", "invoiceid='" . $vars[invoiceid] . "' and notes='gateway_fees'");
    $result = select_query("tbladdonmodules", "setting,value", "setting='fee_2_" . $vars['paymentmethod'] . "' or setting='fee_1_" . $vars[paymentmethod] . "'");
    while ($data = mysql_fetch_array($result)) {
        $params[$data[0]] = $data[1];
    }

    $fee1 = ($params['fee_1_' . $paymentmethod]);
    $fee2 = ($params['fee_2_' . $paymentmethod]);
    $total = InvoiceTotal($vars['invoiceid']);
    if ($total > 0) {
        $amountdue = $fee1 + $total * $fee2 / 100;
        if ($fee1 > 0 & $fee2 > 0) {
            $d = $fee1 . '+' . $fee2 . "%";
        }
        elseif ($fee2 > 0) {
            $d = $fee2 . "%";
        }
        elseif ($fee1 > 0) {
            $d = $fee1;
        }
    }

    if ($d) {
        insert_query("tblinvoiceitems", array(
            "userid" => $_SESSION['uid'],
            "invoiceid" => $vars[invoiceid],
            "type" => "Fee",
            "notes" => "gateway_fees",
            "description" => getGatewayName2($vars['paymentmethod']) . " Fees ($d)",
            "amount" => $amountdue,
            "taxed" => "0",
            "duedate" => "now()",
            "paymentmethod" => $vars[paymentmethod]
        ));
    }

    updateInvoiceTotal($vars[invoiceid]);
}

add_hook("InvoiceChangeGateway", 1, "update_gateway_fee2");
add_hook("InvoiceCreated", 1, "update_gateway_fee1");
add_hook("AdminInvoicesControlsOutput", 2, "update_gateway_fee3");
add_hook("AdminInvoicesControlsOutput", 1, "update_gateway_fee1");
add_hook("InvoiceCreationAdminArea", 1, "update_gateway_fee1");
add_hook("InvoiceCreationAdminArea", 2, "update_gateway_fee3");

function InvoiceTotal($id)
{
    global $CONFIG;
    $result = select_query("tblinvoiceitems", "", array(
        "invoiceid" => $id
    ));
    while ($data = mysql_fetch_array($result)) {
        if ($data['taxed'] == "1") {
            $taxsubtotal+= $data['amount'];
        }
        else {
            $nontaxsubtotal+= $data['amount'];
        }
    }

    $subtotal = $total = $nontaxsubtotal + $taxsubtotal;
    $result = select_query("tblinvoices", "userid,credit,taxrate,taxrate2", array(
        "id" => $id
    ));
    $data = mysql_fetch_array($result);
    $userid = $data['userid'];
    $credit = $data['credit'];
    $taxrate = $data['taxrate'];
    $taxrate2 = $data['taxrate2'];
    if (!function_exists("getClientsDetails")) {
        require_once (dirname(__FILE__) . "/clientfunctions.php");

    }

    $clientsdetails = getClientsDetails($userid);
    $tax = $tax2 = 0;
    if ($CONFIG['TaxEnabled'] == "on" && !$clientsdetails['taxexempt']) {
        if ($taxrate != "0.00") {
            if ($CONFIG['TaxType'] == "Inclusive") {
                $taxrate = $taxrate / 100 + 1;
                $calc1 = $taxsubtotal / $taxrate;
                $tax = $taxsubtotal - $calc1;
            }
            else {
                $taxrate = $taxrate / 100;
                $tax = $taxsubtotal * $taxrate;
            }
        }

        if ($taxrate2 != "0.00") {
            if ($CONFIG['TaxL2Compound']) {
                $taxsubtotal+= $tax;
            }

            if ($CONFIG['TaxType'] == "Inclusive") {
                $taxrate2 = $taxrate2 / 100 + 1;
                $calc1 = $taxsubtotal / $taxrate2;
                $tax2 = $taxsubtotal - $calc1;
            }
            else {
                $taxrate2 = $taxrate2 / 100;
                $tax2 = $taxsubtotal * $taxrate2;
            }
        }

        $tax = round($tax, 2);
        $tax2 = round($tax2, 2);
    }

    if ($CONFIG['TaxType'] == "Inclusive") {
        $subtotal = $subtotal - $tax - $tax2;
    }
    else {
        $total = $subtotal + $tax + $tax2;
    }

    if (0 < $credit) {
        if ($total < $credit) {
            $total = 0;
            $remainingcredit = $total - $credit;
        }
        else {
            $total-= $credit;
        }
    }

    $subtotal = format_as_currency($subtotal);
    $tax = format_as_currency($tax);
    $total = format_as_currency($total);
    return $total;
}

function getGatewayName2($modulename)
{
    $result = select_query("tblpaymentgateways", "value", array(
        "gateway" => $modulename,
        "setting" => "name"
    ));
    $data = mysql_fetch_array($result);
    return $data["value"];
}

?>
