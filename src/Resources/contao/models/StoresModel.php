<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2021 Leo Feyer
 *
 * @package   StoreLocator
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL
 * @copyright 2021 numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\StoreLocator;

use Contao\Database;
use Contao\Model;
use Contao\Model\Collection;

/**
 * Reads stores.
 *
 * @property int    $id
 * @property int    $tstamp
 * @property string $name
 * @property string $alias
 * @property string $email
 * @property string $url
 * @property string $phone
 * @property string $fax
 * @property string $street
 * @property string $postal
 * @property string $city
 * @property string $country
 * @property string $opening_times
 * @property string $longitude
 * @property string $latitude
 * @property bool $addImage
 * @property string $multiSRC
 * @property string $orderSRC
 * @property bool    $published
 * @property string $highlight
 *
 * @method static Collection|StoresModel[]|null findAll(array $opt = array())
 */
class StoresModel extends Model {


    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_storelocator_stores';


    /**
     * Return a list of stores near the given location, results can be filtered by distance, number and categories
     *
     * @param integer $latitude
     * @param integer $longitude
     * @param integer $distance
     * @param integer $limit
     * @param array|null $categories
     * @param string|null $filter
     * @param string|null $order
     *
     * @return Collection
     */
    public static function searchNearby(int $latitude, int $longitude, int $distance=0, int $limit=0, ?array $categories=NULL, ?string $filter=NULL, ?string $order=NULL): Collection
    {

        $objStores = Database::getInstance()->prepare("
            SELECT
                *
            , 3956 * 1.6 * 2 * ASIN(SQRT( POWER(SIN((? -abs(latitude)) * pi()/180 / 2),2) + COS(? * pi()/180 ) * COS( abs(latitude) *  pi()/180) * POWER(SIN((?-longitude) *  pi()/180 / 2), 2) )) AS distance
            FROM ".self::$strTable."
            WHERE
                published='1'
                AND pid IN(".implode(',',$categories).")
                AND latitude != ''
                AND longitude != ''
                ".($filter? "AND ".$filter:"")."
            ".(($distance>0) ? "HAVING distance < $distance ": '')."
            ORDER BY ".($order?$order.", ":"")."distance ASC, highlight DESC
            ".(($limit>0) ? "LIMIT $limit ": '')."
        ")->execute(
            $latitude
        ,   $latitude
        ,   $longitude
        );

        return self::createCollectionFromDbResult($objStores, self::$strTable);
    }


    /**
     * Return a list of stores in the given country location, results can be filtered by number and categories
     *
     * @param string $country
     * @param integer $limit
     * @param array|null $categories
     * @param string|null $filter
     * @param string|null $order
     *
     * @return Collection
     */
    public static function searchCountry(string $country, int $limit=0, ?array $categories=NULL, ?string $filter=NULL, ?string $order=NULL ): Collection
    {

        $objStores = Database::getInstance()->prepare("
            SELECT
                *
            FROM ".self::$strTable."
            WHERE
                published='1'
                AND pid IN(".implode(',',$categories).")
                ".(($country) ? "AND country = '$country' ": '')."
                ".($filter? "AND ".$filter:"")."
            ORDER BY ".($order?$order.", ":"")."highlight DESC
            ".(($limit>0) ? "LIMIT $limit ": '')."
        ")->execute();

        return self::createCollectionFromDbResult($objStores, self::$strTable);
    }


    /**
     * Return a list of stores in the given geocoordinates, results can be filtered by categories
     *
     * @param integer $formLng
     * @param integer $toLng
     * @param integer $formLat
     * @param integer $toLat
     * @param integer $limit
     * @param array|null $categories
     * @param string|null $filter
     *
     * @return Collection
     */
    public static function searchBetweenCoords(int $formLng, int $toLng, int $formLat, int $toLat, int $limit=0, ?array $categories=NULL, ?string $filter=NULL ): Collection
    {

        $objStores = Database::getInstance()->prepare("
            SELECT
                *
            FROM tl_storelocator_stores
            WHERE
                published='1'
                AND latitude != ''
                AND longitude != ''
                AND ? < longitude AND longitude < ?
                AND ? < latitude AND latitude < ?
                ".($categories? "AND pid IN(".implode(',',$categories).")":"")."
                ".($filter? "AND ".$filter:"")."
            ".(($limit>0) ? "LIMIT $limit ": 'LIMIT 500')."
        ")->execute((float)$formLng, (float)$toLng, (float)$formLat, (float)$toLat);

        return self::createCollectionFromDbResult($objStores, self::$strTable);
    }
}
