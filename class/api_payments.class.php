<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2020   Thibault FOUCART     	<support@ptibogxiv.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';

/**
 * API class for users
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class PaymentsApi extends DolibarrApi
{
    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array();


    /**
     * Constructor
     */
    public function __construct()
    {
        global $db, $conf;

        $this->db = $db;
    }

    /**
     * List payments
     *
     * Get a list of payments
     *
     * @param string	$element	  Element ('invoice' ou 'invoice_supplier')
     * @return array                      Array of invoice objects
     *
     * @throws RestException 404 Not found
     * @throws RestException 503 Error
     */
    public function index($element = "invoice")
    {
        global $db, $conf;

        if (!DolibarrApiAccess::$user->hasRight('facture', 'lire')) {
            throw new RestException(401);
        }

        $obj_ret = array();

        $table = 'paiement_facture';
        $table2 = 'paiement';
        $field = 'fk_facture';
        $field2 = 'fk_paiement';
        $field3 = ', p.ref_ext';
        $field4 = ', p.fk_bank'; // Bank line id
        $sharedentity = 'facture';
        if ($element == 'facture_fourn' || $element == 'invoice_supplier') {
            $table = 'paiementfourn_facturefourn';
            $table2 = 'paiementfourn';
            $field = 'fk_facturefourn';
            $field2 = 'fk_paiementfourn';
            $field3 = '';
            $sharedentity = 'facture_fourn';
        }

        $sql = "SELECT p.ref, pf.amount,  pf.".$field." as fk_object, pf.multicurrency_amount, p.fk_paiement, p.datep, p.num_paiement as num, t.code".$field3 . $field4;
        $sql .= " FROM ".$this->db->prefix().$table." as pf, ".$this->db->prefix().$table2." as p, ".$this->db->prefix()."c_paiement as t";
        $sql .= " WHERE pf.".$field." > 0";
        $sql .= " AND pf.".$field2." = p.rowid";
        $sql .= ' AND p.fk_paiement = t.id';
        $sql .= ' AND p.entity IN ('.getEntity($sharedentity).')';

        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                $tmp = array('amount'=>$obj->amount, 'fk_object'=>$obj->fk_object, 'type'=>$obj->code, 'date'=>$this->db->jdate($obj->datep), 'num'=>$obj->num, 'ref'=>$obj->ref);
                $obj_ret[] = $tmp;
                $i++;
            }
            $this->db->free($resql);
        } else {
            throw new RestException(503, 'Error when retrieve payments list : '.$this->db->lasterror());
        }

        return $obj_ret;
    }

}
