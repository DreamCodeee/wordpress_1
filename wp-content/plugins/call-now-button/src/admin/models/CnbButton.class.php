<?php
// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

class CnbButton {

    /**
     * @var string
     */
    public $id;

    /**
     * @var boolean
     */
    public $active;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $type;

    public $domain;

    public $actions;

    public $conditions;

    /**
     * @var CnbButtonOptions
     */
    public $options;

    public static function setSaneDefault($button) {
        // Set some sane defaults
        if (!isset($button->options)) $button->options = new CnbButtonOptions();
        $button->options->iconBackgroundColor = !empty($button->options->iconBackgroundColor)
            ? $button->options->iconBackgroundColor
            : '#009900';
        $button->options->iconColor = !empty($button->options->iconColor)
            ? $button->options->iconColor
            : '#FFFFFF';
        $button->options->placement = !empty($button->options->placement)
            ? $button->options->placement
            : ($button->type === 'FULL' ? 'BOTTOM_CENTER' : 'BOTTOM_RIGHT');
        $button->options->scale = !empty($button->options->scale)
            ? $button->options->scale
            : '1';
    }

    public static function createDummyButton($domain = null) {
        $button = new CnbButton();
        $button->id = '';
        $button->active = false;
        $button->name = '';
        $button->type = 'SINGLE';
        $button->domain = $domain;
        $button->actions = array();
        $button->conditions = array();

        return $button;
    }

    public function toArray() {
        return array(
            'id' => $this->id,
            'active' => $this->active,
            'name' => $this->name,
            'type' => $this->type,
            'domain' => $this->domain,
            'actions' => $this->actions,
            'conditions' => $this->conditions,
            'options' => isset($this->options) ? $this->options->toArray() : array()
        );
    }

    public static function fromObject($object) {
        $button = new CnbButton();
        $button->id = $object->id;
        $button->active = $object->active;
        $button->name = $object->name;
        $button->type = $object->type;
        $button->domain = $object->domain;
        $button->actions = $object->actions;
        $button->conditions = $object->conditions;
        $button->options = CnbButtonOptions::fromObject($object->options);

        return $button;
    }

    /**
     * @param $buttons CnbButton[]
     *
     * @return array
     */
    public static function convert($buttons) {
        return array_map(
            function($button) {
                $button = $button instanceof CnbButton ? $button : self::fromObject($button);
                return ($button instanceof CnbButton) ? $button->toArray() : array();
                }, $buttons
        );
    }
}

class CnbButtonOptions {
    public $iconBackgroundColor;
    public $iconColor;
    /**
     * @var string
     */
    public $placement;
    public $scale;

    public function toArray() {
        return array(
            'iconBackgroundColor' => $this->iconBackgroundColor,
            'iconColor'           => $this->iconColor,
            'placement'           => $this->placement,
            'scale'               => $this->scale
        );
    }

    public static function fromObject( $object ) {
        $options                      = new CnbButtonOptions();
        $options->iconBackgroundColor = $object->iconBackgroundColor;
        $options->iconColor           = $object->iconColor;
        $options->placement           = $object->placement;
        $options->scale               = $object->scale;

        return $options;
    }
}
