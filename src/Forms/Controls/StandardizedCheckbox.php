<?php
/**
 * Created by IntelliJ IDEA.
 * User: stopka
 * Date: 9.12.17
 * Time: 13:24
 */

namespace Stopka\NetteFormRenderer\Forms\Controls;


use Nette\Forms\Controls\Checkbox;

/**
 * Class StandardizedCheckbox
 * @package Stopka\NetteFormRenderer\Forms\Controls
 */
class StandardizedCheckbox extends Checkbox {

    private $caption;

    public function setLabelPart($caption){
        $this->caption = $caption;
    }

    public function getLabel($caption = NULL) {
        return parent::getLabelPart();
    }

    public function getLabelPart() {
        return $this->caption;
    }


}