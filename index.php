<?php
/**
 * Copyright (C) 2007,2008  Arie Nugraha (dicarve@yahoo.com)
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

use SLiMS\Plugins;

// IP based access limitation
require LIB.'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-membership');
require SB.'admin/default/session.inc.php';
require SB.'admin/default/session_check.inc.php';
require SIMBIO.'simbio_DB/simbio_dbop.inc.php';

function get_membership_type($dbs){
    $q = $dbs->query("SELECT * FROM mst_member_type ORDER BY member_type_name ASC");
    if($q->num_rows > 0){
        while ($a = $q->fetch_assoc()) {
            $s[] = $a;
        }
        return $s;
    }
    return false;
}

if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    if ($_POST['action'] == 'get_members') {
        $class_id = $dbs->escape_string(isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0);
        $page = $dbs->escape_string(isset($_POST['page']) ? (int)$_POST['page'] : 1);
        $records_per_page = 10; 
        $offset = ($page - 1) * $records_per_page;
        if ($class_id > 0) {
            $t_m = $dbs->query("SELECT member_id FROM member WHERE member_type_id = '".$class_id."'");
            $total_records = $t_m->num_rows;
            $total_pages = ceil($total_records / $records_per_page);
            $members = [];
            $member_q = $dbs->query("SELECT s.member_id, s.member_name, k.member_type_name 
                FROM member s JOIN mst_member_type k ON s.member_type_id = k.member_type_id 
                WHERE s.member_type_id = '".$class_id."' 
                ORDER BY s.member_name ASC LIMIT $records_per_page OFFSET $offset");
                if($member_q->num_rows > 0){
                    while ($a = $member_q->fetch_assoc()) {
                        $members[] = $a;
                    }
                }
            echo json_encode([
                'success' => true,
                'members' => $members,
                'pagination' => [
                'totalPages' => $total_pages,
                'currentPage' => $page
                ]
            ]);
        } else {
            echo json_encode([
                'success' => true, 
                'members' => [], 
                'pagination' => ['totalPages' => 0, 'currentPage' => 1]
            ]);
        }
        exit;
    }
    if ($_POST['action'] == 'move_members') {
        $member_ids = isset($_POST['member_ids']) ? $_POST['member_ids'] : [];
        $destination_class_id = $dbs->escape_string(isset($_POST['destination_class_id']) ? (int)$_POST['destination_class_id'] : 0);
        $success = 0;
        if (empty($member_ids) || $destination_class_id == 0) {
            echo json_encode(['success' => false, 'message' => 'No members selected or destination membership type is invalid.']);
            exit;
        }
        try {
            foreach ($member_ids as $memberID) {
                $memberID = $dbs->escape_string($memberID);
                // save old membership type to database
                $log = $dbs->query("INSERT IGNORE INTO membertype_log 
                    SELECT member_id, member_type_id, now(), '".$_SESSION['uid']."'
                    FROM member WHERE member_id = '".$memberID."'");
                $update = $dbs->query("UPDATE member SET member_type_id = '".$destination_class_id."', last_update = '".date("Y-m-d")."' 
                WHERE member_id = '".$memberID."'");
                if($update){
                    $success++;
                }
            }
            echo json_encode(['success' => true, 'message' => $success . ' member has been successfully transferred.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}
$classes = get_membership_type($dbs);

?>

<div class="menuBox">
  <div class="menuBoxInner systemIcon">
    <div class="per_title">
      <h2><?= __('Update Member Type') ?></h2>
    </div>
    <div class="infoBox"><?= __('Modify member type') ?></div>
  </div>
</div>
   <div class="mainContent p-5">
        <div id="alert-container" class="alert-container"></div>
        <div class="row mb-4">
            <div class="col-md-5">
                <label for="source_class" class="form-label"><strong><?= __('From')?></strong></label>
                <select id="source_class" class="form-control form-select form-select-lg">
                    <option value="0" selected>-- <?= __('Select Original Membership Type')?> --</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?= htmlspecialchars($class['member_type_id']) ?>"><?= htmlspecialchars($class['member_type_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 text-center d-flex align-items-end justify-content-center">
                 <i class="fa fa-arrow-right fa-2x text-primary"></i>
            </div>
            <div class="col-md-5">
                <label for="destination_class" class="form-label"><strong><?= __('To') ?></strong></label>
                <select id="destination_class" class="form-control form-select form-select-lg">
                    <option value="0" selected>-- <?= __('Select Destination Membership Type') ?> --</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?= htmlspecialchars($class['member_type_id']) ?>"><?= htmlspecialchars($class['member_type_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <?= __('Members in the Origin membership type') ?>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="select_all_members">
                                <label class="form-check-label" for="select_all_members">
                                    <?= __('SELECT ALL')?>
                                </label>
                            </div>
                        </div>
                        <div id="source_member_list" class="member-list list-group">
                            <p class="text-center text-muted mt-3"><?= __('Please select the original membership type first.') ?></p>
                        </div>
                    </div>
                    <div class="card-footer bg-light">
                        <nav>
                            <ul class="pagination justify-content-center" id="source_pagination">
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="col-md-2 move-button-container">
                <div class="d-grid gap-2">
                <button id="move_button" class="btn btn-primary btn-lg shadow" disabled style="width: 100%;">
                    <?= __('move it') ?> <i class="fa fa-chevron-right"></i>
                </button>
            </div>
            </div>

            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <?= __('Members in the Destination Membership Type') ?>
                    </div>
                    <div class="card-body">
                        <div id="destination_member_list" class="member-list list-group">
                             <p class="text-center text-muted mt-3"><?= __('Please select the target membership type first.') ?></p>
                        </div>
                    </div>
                    <div class="card-footer bg-light">
                        <nav>
                            <ul class="pagination justify-content-center" id="destination_pagination">
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
</div>

    <script>
    $(document).ready(function() {

        function showAlert(message, type = 'success') {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>`;
            $('#alert-container').html(alertHtml);
            setTimeout(() => {
                $('.alert').alert('close');
            }, 5000);
        }
        
        function renderPagination(totalPages, currentPage, paginationContainerId, listType) {
            const container = $(`#${paginationContainerId}`);
            container.empty();

            if (totalPages <= 1) {
                return;
            }

            currentPage = parseInt(currentPage);

            let prevDisabled = (currentPage === 1) ? 'disabled' : '';
            container.append(`<li class="page-item ${prevDisabled}"><div class="page-link" data-page="${currentPage - 1}" data-list-type="${listType}"><?= __('PREV') ?></div></li>`);

            for (let i = 1; i <= totalPages; i++) {
                let activeClass = (i === currentPage) ? 'active' : '';
                container.append(`<li class="page-item ${activeClass}"><div class="page-link" data-page="${i}" data-list-type="${listType}">${i}</div></li>`);
            }

            let nextDisabled = (currentPage === totalPages) ? 'disabled' : '';
            container.append(`<li class="page-item ${nextDisabled}"><div class="page-link" data-page="${currentPage + 1}" data-list-type="${listType}"><?= __('NEXT')?></div></li>`);
        }

        function loadMembers(classId, targetListId, paginationContainerId, isSource = false, page = 1) {
            const targetList = $(`#${targetListId}`);
            targetList.html('<p class="text-center text-muted mt-3"><i class="fa fa-spinner fa-spin"></i> Loading ...</p>');
            $(`#${paginationContainerId}`).empty(); 

            if (!classId || classId == '0') {
                let message = isSource ? 'Please select the original membership type first.' : 'Please select the target membership type first.';
                targetList.html(`<p class="text-center text-muted mt-3">${message}</p>`);
                return;
            }

            $.ajax({
                url: '<?=$_SERVER['REQUEST_URI']?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'get_members',
                    class_id: classId,
                    page: page
                },
                success: function(response) {
                    targetList.empty();
                    if (response.success && response.members.length > 0) {
                        response.members.forEach(member => {
                            let memberHtml = '';
                            if (isSource) {
                                memberHtml = `
                                <label class="list-group-item list-group-item-action" style="padding-left: 35px;">
                                    <input class="form-check-input member-checkbox" type="checkbox" value="${member.member_id}">
                                    <div class="member-info"><small> ${member.member_id}</small> - <strong>${member.member_name}</strong></div>
                                </label>`;
                            } else {
                                memberHtml = `
                                <div class="list-group-item">
                                    <div class="member-info"><small> ${member.member_id}</small> - <strong>${member.member_name}</strong></div>
                                </div>`;
                            }
                            targetList.append(memberHtml);
                        });
                        const paginationData = response.pagination;
                        const listType = isSource ? 'source' : 'destination';
                        renderPagination(paginationData.totalPages, paginationData.currentPage, paginationContainerId, listType);
                    } else {
                        targetList.html('<p class="text-center text-muted mt-3">There are no members in this membership type</p>');
                    }
                },
                error: function() {
                    targetList.html('<p class="text-center text-danger mt-3">Failed to load member data</p>');
                }
            });
        }

        $('#source_class').on('change', function() {
            const classId = $(this).val();
            loadMembers(classId, 'source_member_list', 'source_pagination', true, 1);
            $('#select_all_members').prop('checked', false);
            checkMoveButtonState();
        });

        $('#destination_class').on('change', function() {
            const classId = $(this).val();
            loadMembers(classId, 'destination_member_list', 'destination_pagination', false, 1);
            checkMoveButtonState();
        });

        $(document).on('click', '.pagination .page-link', function(e) {


            const page = $(this).data('page');
            const listType = $(this).data('list-type');

            console.log(page);
            if ($(this).parent().hasClass('disabled') || $(this).parent().hasClass('active')) {
                return; 
            }

            if (listType === 'source') {
                const classId = $('#source_class').val();
                loadMembers(classId, 'source_member_list', 'source_pagination', true, page);
            } else if (listType === 'destination') {
                const classId = $('#destination_class').val();
                loadMembers(classId, 'destination_member_list', 'destination_pagination', false, page);
            }

        });

        $('#select_all_members').on('change', function() {
            $('.member-checkbox').prop('checked', this.checked);
            checkMoveButtonState();
        });
        
        $(document).on('change', '.member-checkbox', function() {
            if ($('.member-checkbox:checked').length === $('.member-checkbox').length) {
                $('#select_all_members').prop('checked', true);
            } else {
                $('#select_all_members').prop('checked', false);
            }
            checkMoveButtonState();
        });

        function checkMoveButtonState() {
            const sourceClassId = $('#source_class').val();
            const destClassId = $('#destination_class').val();
            const membersSelected = $('.member-checkbox:checked').length > 0;

            if (sourceClassId != '0' && destClassId != '0' && sourceClassId != destClassId && membersSelected) {
                $('#move_button').prop('disabled', false);
            } else {
                $('#move_button').prop('disabled', true);
            }
        }

        $('#move_button').on('click', function() {
            const sourceClassId = $('#source_class').val();
            const destinationClassId = $('#destination_class').val();
            const memberIds = $('.member-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            if (sourceClassId == destinationClassId) {
                showAlert('The origin and destination membership types cannot be the same.', 'danger');
                return;
            }

            if (memberIds.length === 0) {
                showAlert('Please select at least one member to move.', 'warning');
                return;
            }

            if (!confirm(`Are you sure you want to move ${memberIds.length} members to the target membership type?`)) {
                return;
            }

            $.ajax({
                url: '<?=$_SERVER['REQUEST_URI']?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'move_members',
                    member_ids: memberIds,
                    destination_class_id: destinationClassId
                },
                success: function(response) {
                    if (response.success) {
                        showAlert(response.message, 'success');
                        loadMembers(sourceClassId, 'source_member_list', 'source_pagination', true, 1);
                        loadMembers(destinationClassId, 'destination_member_list', 'destination_pagination', false, 1);
                        $('#select_all_members').prop('checked', false);
                        checkMoveButtonState();
                    } else {
                        showAlert(response.message, 'danger');
                    }
                },
                error: function() {
                    showAlert('An error occurred while communicating with the server.', 'danger');
                }
            });
        });
    });
    </script>