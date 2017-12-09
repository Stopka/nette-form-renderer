<?php

namespace Stopka\NetteFormRenderer\Forms\Rendering;

use Nette;
use Nette\Forms\Controls;
use Nette\Utils\Html as Html;
use Stopka\NetteFormRenderer\Forms\IFormOptionKeys;

class BetterFormRenderer implements Nette\Forms\IFormRenderer, IFormOptionKeys {
    use Nette\SmartObject;
    protected const OPTION_KEY_RENDERED = 'rendered';

    const RENDER_MODE_BEGIN = 'begin';
    const RENDER_MODE_ERRORS = 'errors';
    const RENDER_MODE_OWNERRORS = 'ownerrors';
    const RENDER_MODE_BODY = 'body';
    const RENDER_MODE_END = 'end';
    const RENDER_MODE_GROUP = 'group';
    const RENDER_MODE_GROUPCONTROLS = 'groupcontrols';
    const RENDER_MODE_BUTTONS = 'buttons';
    const RENDER_MODE_CONTROLS = 'controls';
    const RENDER_MODE_PAIR = 'pair';
    const RENDER_MODE_CONTROL = 'control';

    /**
     *  /--- form.container
     *
     *    /--- error.container
     *      .... error.item [.class]
     *    \---
     *
     *    /--- hidden.container
     *      .... HIDDEN CONTROLS
     *    \---
     *
     *    /--- group.container
     *      .... group.label
     *      .... group.description
     *
     *      /--- controls.container
     *
     *        /--- pair.container [.required .optional .odd]
     *
     *          /--- label.container
     *            .... LABEL
     *            .... label.suffix
     *            .... label.requiredsuffix
     *          \---
     *
     *          /--- control.container [.odd]
     *            .... CONTROL [.required .text .password .file .submit .button]
     *            .... control.requiredsuffix
     *            .... control.description
     *            .... control.errorcontainer + control.erroritem
     *          \---
     *        \---
     *      \---
     *    \---
     *  \--
     *
     * @var array of HTML tags
     */
    public $wrappers = array(
        'form' => array(
            'container' => null,
        ),
        'error' => array(
            'container' => 'div class="alert alert-error"',
            'item' => 'div',
        ),
        'group' => array(
            'container' => 'fieldset',
            'logicalContainer' => 'div class="form-group"',
            'label' => 'legend',
            'description' => 'div class="description group-description"',
        ),
        'controls' => array(
            'container' => 'div',
        ),
        'pair' => array(
            'container' => 'div class="control-group control-pair"',
            '.required' => 'required',
            '.optional' => null,
            '.odd' => 'odd',
            '.error' => 'has-error',
        ),
        'multi' => array(
            'container' => 'div class="control-group control-multi"',
            '.required' => 'required',
            '.optional' => null,
            '.odd' => 'odd',
            '.error' => 'has-error',
        ),
        'control' => array(
            'container' => 'div class=controls',
            '.odd' => 'odd',
            'description' => 'div class="description control-description"',
            'requiredsuffix' => '',
            'errorcontainer' => 'span class=form-error-message',
            'erroritem' => '',
            '.required' => 'required',
            '.text' => 'text',
            '.password' => 'text',
            '.file' => 'text',
            '.submit' => 'button',
            '.image' => 'imagebutton',
            '.button' => 'button',
        ),
        'label' => array(
            'container' => 'div class="control-label"',
            'suffix' => null,
            'requiredsuffix' => '',
        ),
        'hidden' => array(
            'container' => 'div class="hidden-controls"',
        ),
    );

    /** @var Nette\Forms\Form */
    protected $form;

    /** @var int */
    protected $counter;

    /** @var  Nette\Forms\ControlGroup[] */
    protected $groups;

    /** @var  Nette\Forms\IControl[] */
    protected $controls;

    /** @var  Controls\Button[] */
    protected $action_buttons;

    /**
     * Provides complete form rendering.
     * @param  Nette\Forms\Form $form
     * @param  $mode null|string|Nette\Forms\Container|Nette\Forms\ControlGroup|Nette\Forms\IControl 'begin', 'errors', 'ownerrors', 'body', 'end' or empty to render all
     * @return string
     * @throws FormRenderException
     */
    public function render(Nette\Forms\Form $form, $mode = NULL): string {
        if ($this->form !== $form) {
            $this->form = $form;
        }
        if (!$mode) {
            return $this->renderDefault();
        }
        return $this->renderMode($mode);
    }

    /**
     * Converts legacy embedNext group options to embed options
     */
    protected function prepareGroups() {
        $parent = [];
        $counter = [];
        foreach ($this->form->getGroups() as $group) {
            if ($counter && $counter[0] > 0) {
                $embeded = $parent[0]->getOption(self::OPTION_KEY_EMBED);
                if (!$embeded) {
                    $embeded = [];
                }
                $embeded[] = $group;
                $parent[0]->setOption(self::OPTION_KEY_EMBED, $embeded);
                $counter[0]--;
            }
            $next = $group->getOption(self::OPTION_KEY_EMBED_NEXT);
            if ($next === TRUE) {
                $next = 1;
            }
            if ($next) {
                array_unshift($counter, $next);
                array_unshift($parent, $group);
                continue;
            }
            while ($counter && $counter[0] === 0) {
                array_shift($parent);
                array_shift($counter);
            }
        }
    }

    /**
     * Renders default layout of form
     * @return string
     */
    public function renderDefault(): string {
        $s = '';
        $s .= $this->renderBegin();
        $s .= $this->renderErrors();
        $s .= $this->renderBody();
        $s .= $this->renderEnd();
        return $s;
    }

    /**
     * Returns rendered part of form
     * @param string|Nette\Forms\Container|Nette\Forms\ControlGroup|Nette\Forms\IControl $mode
     * @return string
     * @throws FormRenderException
     */
    public function renderMode($mode): string {
        if (is_object($mode)) {
            if ($mode instanceof Nette\Forms\Container) {
                return $this->renderGroupControls($mode);
            }
            if ($mode instanceof Nette\Forms\ControlGroup) {
                return $this->renderGroup($mode);
            }
            if ($mode instanceof Nette\Forms\IControl) {
                return $this->renderControl($mode);
            }
            throw new FormRenderException('Unsupported form render object mode ' . get_class($mode));
        }
        list($mode, $name) = explode(':', $mode . ':');
        switch (strtolower($mode)) {
            case self::RENDER_MODE_BEGIN:
                return $this->renderBegin();
            case self::RENDER_MODE_OWNERRORS:
                return $this->renderErrors();
            case self::RENDER_MODE_ERRORS:
                return $this->renderErrors(NULL, FALSE);
            case self::RENDER_MODE_BODY:
                return $this->renderBody();
            case self::RENDER_MODE_GROUPCONTROLS:
                $group = $this->form->getGroup($name);
                return $this->renderGroupControls($group);
            case self::RENDER_MODE_BUTTONS:
                return $this->renderButtons();
            case self::RENDER_MODE_CONTROLS:
                return $this->renderControls();
            case self::RENDER_MODE_END:
                return $this->renderEnd();
            case self::RENDER_MODE_PAIR:
                $control = $this->form[$name];
                return $this->renderPair($control);
            case self::RENDER_MODE_CONTROL:
                $control = $this->form[$name];
                return $this->renderControl($control);
            case self::RENDER_MODE_GROUP:
                $group = $this->form->getGroup($name);
                return $this->renderGroup($group);
            default:
                throw new FormRenderException("Unsupported form renderer mode '$mode'");
        }
    }


    /**
     * Renders form begin.
     * @return string
     */
    public function renderBegin(): string {
        $this->counter = 0;

        foreach ($this->form->getControls() as $control) {
            $control->setOption(self::OPTION_KEY_RENDERED, FALSE);
        }

        if ($this->form->isMethod('get')) {
            $el = clone $this->form->getElementPrototype();
            $query = parse_url($el->action, PHP_URL_QUERY);
            $el->action = str_replace("?$query", '', $el->action);
            $s = '';
            foreach (preg_split('#[;&]#', $query, NULL, PREG_SPLIT_NO_EMPTY) as $param) {
                $parts = explode('=', $param, 2);
                $name = urldecode($parts[0]);
                if (!isset($this->form[$name])) {
                    $s .= Html::el('input', ['type' => 'hidden', 'name' => $name, 'value' => urldecode($parts[1])]);
                }
            }
            return $el->startTag() . ($s ? "\n\t" . $this->getWrapper('hidden container')->setHtml($s) : '');

        } else {
            return $this->form->getElementPrototype()->startTag();
        }
    }


    /**
     * Renders form end.
     * @return string
     */
    public function renderEnd(): string {
        $s = '';
        foreach ($this->form->getControls() as $control) {
            if ($control->getOption(self::OPTION_KEY_TYPE) === 'hidden' && !$control->getOption(self::OPTION_KEY_RENDERED)) {
                $s .= $control->getControl();
            }
        }
        if (iterator_count($this->form->getComponents(TRUE, Nette\Forms\Controls\TextInput::class)) < 2) {
            $s .= '<!--[if IE]><input type=IEbug disabled style="display:none"><![endif]-->';
        }
        if ($s) {
            $s = $this->getWrapper('hidden container')->setHtml($s) . "\n";
        }

        return $s . $this->form->getElementPrototype()->endTag() . "\n";
    }


    /**
     * Renders validation errors (per form or per control).
     * @param Nette\Forms\IControl $control
     * @param bool $own
     * @return string
     */
    public function renderErrors(Nette\Forms\IControl $control = NULL, bool $own = TRUE): string {
        $errors = $control
            ? $control->getErrors()
            : ($own ? $this->form->getOwnErrors() : $this->form->getErrors());
        if (!$errors) {
            return "";
        }
        $container = $this->getWrapper($control ? 'control errorcontainer' : 'error container');
        $item = $this->getWrapper($control ? 'control erroritem' : 'error item');

        foreach ($errors as $error) {
            $item = clone $item;
            if ($error instanceof Html) {
                $item->addHtml($error);
            } else {
                $item->setText($error);
            }
            $container->addHtml($item);
        }
        return "\n" . $container->render($control ? 1 : 0);
    }


    /**
     * Renders form body.
     * @return string
     */
    public function renderBody(): string {
        $s = '';

        foreach ($this->form->getGroups() as $group) {
            $s .= $this->renderGroup($group);
        }

        $s .= $this->renderGroupControls($this->form);

        $container = $this->getWrapper('form container');
        $container->setHtml($s);
        return $container->render(0);
    }

//    /**
//     * Přidá do html elementu data atributy pro helpbox
//     * @param Nette\Utils\Html $html
//     * @param Array $help
//     * @return Nette\Utils\Html
//     */
//    protected function appendHelpBoxData($html, $help) {
//        if($this->form instanceof Nette\Application\UI\Form){
//            $presenter = $this->form->getPresenter(false);
//            if($presenter){
//                /** @var Linker $linker */
//                $linker=$presenter->getContext()->getService('forum_linker');
//                $linker->addHelpAttributes($help,$html);
//            }
//        }
//        return $html;
//    }

    /**
     * Renders group of controls.
     * @param  Nette\Forms\Container|Nette\Forms\ControlGroup $group
     * @return string
     * @throws FormRenderException
     */
    public function renderGroup($group): string {
        if ($group->getOption(self::OPTION_KEY_RENDERED)) {
            return '';
        }
        if (!$group->getOption(self::OPTION_KEY_VISUAL) && !$group->getOption(self::OPTION_KEY_LOGICAL)) {
            return $this->renderGroupControls($group);
        }
        $logical = $group->getOption(self::OPTION_KEY_LOGICAL);
        $defaultContainer = $this->getWrapper($logical ? 'group logicalContainer' : 'group container');
        $translator = $this->form->getTranslator();

        $s = '';

        //Group container
        $container = $group->getOption(self::OPTION_KEY_CONTAINER, $defaultContainer);
        $container = $container instanceof Html ? clone $container : Html::el($container);

        //Add attributes to container
        $id = $group->getOption(self::OPTION_KEY_ID);
        if ($id) {
            $container->id = $id;
        }
        $class = $group->getOption(self::OPTION_KEY_CLASS);
        if ($class) {
            $container->addClass($class);
        }

        //Group container start
        $s .= "\n" . $container->startTag();

        //Group label
        if (!$logical) {
            $text = $group->getOption(self::OPTION_KEY_LABEL);
            $group_label = $this->getWrapper('group label');
            //$this->appendHelpBoxData($group_label, $group->getOption('help'));
            if ($text instanceof Html) {
                $group_label->addHtml($text);
            } elseif (is_string($text)) {
                if ($translator !== NULL) {
                    $text = $translator->translate($text);
                }
                $group_label->setText($text);
            }
            if ($text) {
                $s .= "\n" . $group_label . "\n";
            }
        }

        //Group description
        $text = $group->getOption(self::OPTION_KEY_DESCRIPTION);
        if ($text instanceof Html) {
            $s .= $text;

        } elseif (is_string($text) || is_array($text)) {
            if ($translator !== NULL) {
                $text = $translator->translate($text);
            }
            $s .= $this->getWrapper('group description')->setText($text) . "\n";
        }

        //Group controls
        $s .= $this->renderGroupControls($group);

        //Group embed group
        if ($group->getOption(self::OPTION_KEY_EMBED_NEXT) && !$group->getOption(self::OPTION_KEY_EMBED)) {
            $this->prepareGroups();
        }
        if ($embed_group_names = $group->getOption(self::OPTION_KEY_EMBED)) {
            if (is_string($embed_group_names)) {
                $embed_group_names = [$embed_group_names];
            }
            foreach ($embed_group_names as $embed_group_name) {
                if (is_string($embed_group_name)) {//pokud místo jména byla přijata přímo skupina
                    $embed_group = $this->form->getGroup($embed_group_name);
                } else {
                    $embed_group = $embed_group_name;
                }

                if (!$embed_group) {
                    continue;
                }
                if ($embed_group == $group) {
                    throw new FormRenderException('Group can\'t embed itself');
                }
                $s .= $this->renderGroup($embed_group);
            }
        }

        //Group container end
        $s .= $container->endTag() . "\n";
        $group->setOption(self::OPTION_KEY_RENDERED, true);
        return $s;
    }


    /**
     * Renders controls of group.
     * @param  Nette\Forms\Container|Nette\Forms\ControlGroup
     * @return string
     */
    public function renderGroupControls($parent): string {
        if (!($parent instanceof Nette\Forms\Container || $parent instanceof Nette\Forms\ControlGroup)) {
            throw new Nette\InvalidArgumentException('Argument must be Nette\Forms\Container or Nette\Forms\ControlGroup instance.');
        }
        if (!$parent->getControls()) {
            return '';
        }

        $container = $this->getWrapper('controls container');

        $buttons = NULL;
        foreach ($parent->getControls() as $control) {
            if ($control->getOption(self::OPTION_KEY_RENDERED) || $control->getOption(self::OPTION_KEY_TYPE) === 'hidden' || $control->getForm(FALSE) !== $this->form) {
                continue;
            }
            if ($control->getOption(self::OPTION_KEY_TYPE) === 'button') {
                $buttons[] = $control;
                continue;
            }
            if ($buttons) {
                $container->addHtml($this->renderPairMulti($buttons));
                $buttons = NULL;
            }
            $container->addHtml($this->renderPair($control));
        }
        if ($buttons) {
            $additional_class = $parent instanceof Nette\Forms\Container ? 'form-actions' : NULL;
            $action_buttons = $this->renderPairMulti($buttons, $additional_class);
            $container->addHtml($action_buttons);
        }

        $s = '';
        if (count($container)) {
            $s .= "\n" . $container . "\n";
        }

        return $s;
    }


    /**
     * Renders single visual row.
     * @return string
     */
    public function renderPair(Nette\Forms\IControl $control): string {
        $pair = $this->getWrapper('pair container');
        $pair->addHtml($this->renderLabel($control));
        $pair->addHtml($this->renderControl($control));
        $pair->class($this->getValue($control->isRequired() ? 'pair .required' : 'pair .optional'), TRUE);
        $pair->class($control->hasErrors() ? $this->getValue('pair .error') : NULL, TRUE);
        $pair->class($control->getOption(self::OPTION_KEY_CLASS), TRUE);
        if (++$this->counter % 2) {
            $pair->class($this->getValue('pair .odd'), TRUE);
        }
        $pair->id = $control->getOption(self::OPTION_KEY_ID);
        return $pair->render(0);
    }


    /**
     * Renders single visual row of multiple controls.
     * @param  Nette\Forms\IControl[]
     * @param string|NULL $additional_class třída, která se má přidat kontejneru
     * @return string
     */
    public function renderPairMulti(array $controls, $additional_class = NULL): string {
        $s = [];
        $pair_id = null;
        $pair_classes = [];
        if ($additional_class) {
            $pair_classes[] = $additional_class;
        }
        foreach ($controls as $control) {
            if (!$control instanceof Nette\Forms\IControl) {
                throw new Nette\InvalidArgumentException('Argument must be array of Nette\Forms\IControl instances.');
            }
            $description = $control->getOption(self::OPTION_KEY_DESCRIPTION);
            if ($description instanceof Html) {
                $description = ' ' . $control->getOption(self::OPTION_KEY_DESCRIPTION);

            } elseif (is_string($description) || is_array($description)) {
                if ($control instanceof Nette\Forms\Controls\BaseControl) {
                    $description = is_array($description) ? $control->translate([$description])[0] : $description;
                }
                $description = ' ' . $this->getWrapper('control description')->setText($description);

            } else {
                $description = '';
            }

            $control->setOption(self::OPTION_KEY_RENDERED, TRUE);
            $el = $control->getControl();
            if ($el instanceof Html && $el->getName() === 'input') {
                $el->class($this->getValue("control .$el->type"), TRUE);
            }
            //$help=$control->getOption('help');
            //$this->appendHelpBoxData($el,$help);
            $s[] = $el . $description;
            //Přidání id a tříd z option
            $class = $control->getOption(self::OPTION_KEY_CLASS);
            if ($class) {
                $pair_classes[] = $class;
            }
            $id = $control->getOption(self::OPTION_KEY_ID);
            if ($id) {
                $pair_id = $id;
            }
        }
        $pair = $this->getWrapper('multi container');
        if ($pair_id) {
            $pair->setId($pair_id);
        }
        foreach ($pair_classes as $pair_class) {
            $pair->addClass($pair_class);
        }
        $pair->addHtml($this->renderLabel($control));
        $pair->addHtml($this->getWrapper('control container')->setHtml(implode(' ', $s)));
        return $pair->render(0);
    }


    /**
     * Renders 'label' part of visual row of controls.
     * @return string
     */
    public function renderLabel(Nette\Forms\IControl $control): string {
        $suffix = $this->getValue('label suffix') . ($control->isRequired() ? $this->getValue('label requiredsuffix') : '');
        $label = $control->getLabel();
        if ($label instanceof Html) {
            $label->addHtml($suffix);
            if ($control->isRequired()) {
                $label->class($this->getValue('control .required'), TRUE);
            }
        } elseif ($label != NULL) { // @intentionally ==
            $label .= $suffix;
        }
        return $this->getWrapper('label container')->setHtml($label);
        /*return $this->appendHelpBoxData(
            $this->getWrapper('label container')->setHtml($label),
            $control->getOption('help')
        );*/
    }


    /**
     * Renders 'control' part of visual row of controls.
     * @return string
     */
    public function renderControl(Nette\Forms\IControl $control): string {
        $body = $this->getWrapper('control container');
        //$this->appendHelpBoxData($body,$control->getOption('help'));
        if ($this->counter % 2) {
            $body->class($this->getValue('control .odd'), TRUE);
        }

        $description = $control->getOption(self::OPTION_KEY_DESCRIPTION);
        if ($description instanceof Html) {
            $description = ' ' . $description;

        } elseif (is_string($description)) {
            if ($control instanceof Nette\Forms\Controls\BaseControl) {
                $description = $control->translate($description);
            }
            $description = ' ' . $this->getWrapper('control description')->setText($description);

        } elseif (is_array($description)) {
            if ($control instanceof Nette\Forms\Controls\BaseControl) {
                $description = $control->translate([$description])[0];
            }
            $description = ' ' . $this->getWrapper('control description')->setText($description);
        } else {
            $description = '';
        }

        if ($control->isRequired()) {
            $description = $this->getValue('control requiredsuffix') . $description;
        }

        $control->setOption(self::OPTION_KEY_RENDERED, TRUE);
        $el = $control->getControl();
        if ($el instanceof Html && $el->getName() === 'input') {
            $el->class($this->getValue("control .$el->type"), TRUE);
        }
        return $body->setHtml($el . $description . $this->renderErrors($control));
    }


    /**
     * @param  string
     * @return Html
     */
    protected function getWrapper(string $name): Html {
        $data = $this->getValue($name);
        return $data instanceof Html ? clone $data : Html::el($data);
    }


    /**
     * @param  string
     * @return string
     */
    protected function getValue(string $name): ?string {
        $name = explode(' ', $name);
        $data = &$this->wrappers[$name[0]][$name[1]];
        return $data;
    }

    /**
     * @return string
     * @throws FormRenderException
     */
    public function renderControls(): string {
        return $this->renderMode(self::RENDER_MODE_CONTROLS);
    }

    /**
     * @return string
     * @throws FormRenderException
     */
    public function renderButtons(): string {
        return $this->renderMode(self::RENDER_MODE_BUTTONS);
    }

}