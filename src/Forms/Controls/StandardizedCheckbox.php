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

    private $partCaption;

    public $partLabel;

    public function __construct($label = null) {
        parent::__construct($label);
        $this->partLabel = \Nette\Utils\Html::el('label');
    }


    public function setLabelPart($caption): self {
        $this->partCaption = $caption;
        return $this;
    }

    public function getLabel($caption = NULL) {
        return parent::getLabelPart();
    }

    public function getLabelPart() {
        $label = clone $this->label;
        $label->for = $this->getHtmlId();
        $label->setText($this->translate($this->partCaption));
        return $label;
    }


}