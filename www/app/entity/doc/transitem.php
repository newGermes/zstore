<?php

namespace App\Entity\Doc;

use \App\Entity\Entry;
use \App\Entity\Stock;
use \App\Entity\Store;
use \App\Entity\Item;
use \App\Helper as H;

/**
 * Класс-сущность  документ перекомплектация ТМЦ
 *
 */
class TransItem extends Document {

    public function Execute() {

    
           foreach($this->detaildata as $item){  
           if ($item['st1'] > 0 && $item['st1'] > 0) {   //перемещение партий
                $st1 = Stock::load($item['st1']);
                $st2 = Stock::load($item['st2']);

                $sc = new Entry($this->document_id, 0 - $item['quantity'] * $st1->partion, 0 - $item['quantity']);
                $sc->setStock($st1->stock_id);
                $sc->save();

                $sc = new Entry($this->document_id, $item['quantity'] * $st2->partion, $item['quantity']);
                $sc->setStock($st2->stock_id);
                $sc->save();

                return true;
            }
        }  
        //списываем  со склада
        $fi = Stock::load($this->headerdata['fromitem']);

        $sc = new Entry($this->document_id, 0 - ($this->headerdata["fromquantity"] * $fi->partion), 0 - $this->headerdata["fromquantity"]);
        $sc->setStock($fi->stock_id);

        $sc->save();

        $ti = Item::load($this->headerdata['toitem']);
        $price = round(($this->amount ) / $this->headerdata["toquantity"]);
        $stockto = Stock::getStock($this->headerdata['store'], $ti->item_id, $price, "", "", true);
        $sc = new Entry($this->document_id, $this->headerdata["toquantity"] * $price, $this->headerdata["toquantity"]);
        $sc->setStock($stockto->stock_id);

        $sc->save();


        return true;
    }

    public function generateReport() {

        
        $si = Stock::load($this->headerdata['fromitem']);
        $fi = Item::load($si->item_id);
        $ti = Item::load($this->headerdata['toitem']);
        if ($item['st1'] > 0 && $item['st1'] > 0) {   //перемещение партий
            $st1 = Stock::load($item['st1']);
            $fi = Item::load($st1->item_id);
            $ti = Item::load($st1->item_id);
        }

        $header = array(
            'date' => date('d.m.Y', $this->document_date),
            "from" => Store::load($this->headerdata["store"])->storename,
            "fromitemname" => $fi->itemname . ', ' . $this->headerdata["fromquantity"] . $fi->msr,
            "toitemname" => $ti->itemname . ', ' . $this->headerdata["toquantity"] . $ti->msr,
            "document_number" => $this->document_number,
            "amount" => H::fa($this->amount)
        );
        $report = new \App\Report('transitem.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ПК-000000';
    }

}
