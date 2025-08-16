<?php
/**
 * @Created by          : Heru Subekti (heroe.soebekti@gmail.com)
 * @Date                : 08/02/2021 18:50
 * @File name           : 1_AddTelegram.php
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

use SLiMS\DB;
use SLiMS\Migration\Migration;

class AddMembertype extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    function up()
    {
        DB::getInstance()->query("
            DROP TABLE IF EXISTS `membertype_log`;
            CREATE TABLE `membertype_log` (
              `member_id` varchar(64) COLLATE utf8mb4_bin DEFAULT NULL,
              `member_type_id` int DEFAULT NULL,
              `last_update` datetime DEFAULT NULL,
              `uid` int DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
        ");
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    function down()
    {
        DB::getInstance()->query("
            DROP TABLE IF EXISTS `membertype_log`;
        ");
    }
}