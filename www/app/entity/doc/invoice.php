<?php

namespace App\Entity\Doc;

use \App\Entity\Entry;
use \App\Helper as H;
use \App\Util;

/**
 * Класс-сущность  документ счет фактура
 *
 */
class Invoice extends \App\Entity\Doc\Document {

    public function generateReport() {


        $i = 1;
        $detail = array();

        foreach ($this->detaildata as $value) {

            if (isset($detail[$value['item_id']])) {
                $detail[$value['item_id']]['quantity'] += $value['quantity'];
            } else {
                $detail[] = array("no" => $i++,
                    "tovar_name" => $value['itemname'],
                    "tovar_code" => $value['item_code'],
                    "quantity" => H::fqty($value['quantity']),
                    "price" => H::fa($value['price']),
                    "msr" => $value['msr'],
                    "amount" => H::fa($value['quantity'] * $value['price'])
                );
            }
        }


        $header = array('date' => date('d.m.Y', $this->document_date),
            "_detail" => $detail,
            "customername" => $this->headerdata["customer_name"],
            "phone" => $this->headerdata["phone"],
            "email" => $this->headerdata["email"],
            "notes" => $this->notes,
            "document_number" => $this->document_number,
            "total" => $this->amount,
            "payamount" => H::fa($this->payamount),
            "payed" => H::fa($this->payed),
            "paydisc" => H::fa($this->headerdata["paydisc"])
        );


        $report = new \App\Report('invoice.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        //списываем бонусы
        if ($this->headerdata['paydisc'] > 0) {
            $customer = \App\Entity\Customer::load($this->customer_id);
            if ($customer->discount > 0) {
                return; //процент
            } else {
                $customer->bonus = $customer->bonus - ($this->headerdata['paydisc'] > 0 ? $this->headerdata['paydisc'] : 0 );
                $customer->save();
            }
        }

        if ($this->headerdata['payment'] > 0 && $this->payed > 0) {
            \App\Entity\Pay::addPayment($this->document_id, $this->payed, $this->headerdata['payment'], \App\Entity\Pay::PAY_BASE_OUTCOME);
        }
        return true;
    }

    protected function getNumberTemplate() {
        return 'СФ-000000';
    }
  
    public function getRelationBased() {
        $list = array();
        $list['GoodsIssue'] = 'Расходная накладная';

        return $list;
    }
}
