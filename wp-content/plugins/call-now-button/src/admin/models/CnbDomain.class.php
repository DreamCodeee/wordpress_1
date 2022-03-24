<?php
// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

class CnbDomain {

    public $id;
    public $name;
    public $timezone;
    public $type;
    /**
     * @var CnbDomainProperties
     */
    public $properties;
    /**
     * @var boolean
     */
    public $trackGA;
    /**
     * @var boolean
     */
    public $trackConversion;
    /**
     * @var boolean
     */
    public $renew;

    /**
     *
     * This changes the object itself, settings some sane defaults in case those are missing
     *
     * @param $domain CnbDomain|null
     * @param $domain_id number|null
     *
     * @returns CnbDomain
     */
    public static function setSaneDefault( $domain = null, $domain_id = null ) {
        if (is_wp_error($domain)) {
            return $domain;
        }

        if ( $domain === null ) {
            $domain = new CnbDomain();
        }

        if ( strlen( $domain_id ) > 0 && $domain_id == 'new' && empty( $domain->id ) ) {
            $domain->id = null;
        }
        if ( empty( $domain->timezone ) ) {
            $domain->timezone = null;
        }
        if ( empty( $domain->type ) ) {
            $domain->type = 'FREE';
        }
        if ( empty( $domain->properties ) ) {
            $domain->properties        = new CnbDomainProperties();
        }
        if (empty($domain->properties->scale)) {
            $domain->properties->scale = '1';
        }
        if (empty($domain->properties->debug)) {
            $domain->properties->debug = false;
        }
        if (empty($domain->properties->zindex)) {
            $domain->properties->zindex = 2147483647;
        }

        if ( empty( $domain->name ) ) {
            $domain->name = null;
        }
        if ( ! isset( $domain->trackGA ) ) {
            $domain->trackGA = false;
        }
        if ( ! isset( $domain->trackConversion ) ) {
            $domain->trackConversion = false;
        }
        return $domain;
    }
}

class CnbDomainProperties {
    /**
     * @var number
     */
    public $scale;

    /**
     * @var boolean
     */
    public $debug;
}
