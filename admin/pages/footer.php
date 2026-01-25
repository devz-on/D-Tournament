
<footer class="main-footer">
    <strong>Copyright &copy; 2024 <a href="#" target="_blank">Aimgod eSports</a>.</strong>
    All rights reserved.
    <div class="float-right d-none d-sm-inline-block">
        <b>Version</b> 3.1.0
    </div>
</footer>

<div class="modal fade" id="notification" tabindex="-1" role="dialog" aria-labelledby="" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content" style="overflow-y: auto;">
            <div class="modal-header">
                <h5 class="modal-title" id="">Notifications</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h6>Normal Notifications</h6>
                <ul class="list-group mb-3">
                    <?php
                    $run_get_not = mysqli_query($con, "SELECT * FROM `notification` WHERE category='normal' ORDER BY status='unread' DESC, id ASC");

                    while ($fetched_noty = mysqli_fetch_assoc($run_get_not)) {
                        $display_name = $fetched_noty['team_name'] ?: 'Unknown User';
                    ?>

                        <li class="list-group-item <?php if ($fetched_noty['status'] == "unread") {
                                                        echo "font-weight-bold";
                                                    } ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div><?= htmlspecialchars($display_name) ?> <?= htmlspecialchars($fetched_noty['message']) ?></div>
                                <div class="text-muted">
                                    <?php
                                    $ratingDate = strtotime($fetched_noty['created_at']);
                                    $currentDate = time();
                                    $differenceInSeconds = $currentDate - $ratingDate;
                                    $differenceInDays = floor($differenceInSeconds / (60 * 60 * 24));
                                    if ($differenceInDays == 0) {
                                        echo 'Today';
                                    } elseif ($differenceInDays == 1) {
                                        echo 'Yesterday';
                                    } elseif ($differenceInDays <= 60) {
                                        echo $differenceInDays . ' days ago';
                                    } else {
                                        $differenceInWeeks = ceil($differenceInDays / 7);
                                        echo $differenceInWeeks . ' weeks ago';
                                    }
                                    ?>
                                </div>
                            </div>
                        </li>


                    <?php } ?>

                </ul>

                <h6>System Alerts</h6>
                <ul class="list-group">
                    <?php
                    $run_system_not = mysqli_query($con, "SELECT * FROM `notification` WHERE category='system' ORDER BY status='unread' DESC, id ASC");

                    while ($system_not = mysqli_fetch_assoc($run_system_not)) {
                    ?>
                        <li class="list-group-item <?php if ($system_not['status'] == "unread") {
                                                        echo "font-weight-bold";
                                                    } ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>System:</strong> <?= htmlspecialchars($system_not['message']) ?>
                                    <?php if (!empty($system_not['context'])) { ?>
                                        <pre class="mt-2 mb-0"><?= htmlspecialchars($system_not['context']) ?></pre>
                                    <?php } ?>
                                </div>
                                <div class="text-muted">
                                    <?php
                                    $ratingDate = strtotime($system_not['created_at']);
                                    $currentDate = time();
                                    $differenceInSeconds = $currentDate - $ratingDate;
                                    $differenceInDays = floor($differenceInSeconds / (60 * 60 * 24));
                                    if ($differenceInDays == 0) {
                                        echo 'Today';
                                    } elseif ($differenceInDays == 1) {
                                        echo 'Yesterday';
                                    } elseif ($differenceInDays <= 60) {
                                        echo $differenceInDays . ' days ago';
                                    } else {
                                        $differenceInWeeks = ceil($differenceInDays / 7);
                                        echo $differenceInWeeks . ' weeks ago';
                                    }
                                    ?>
                                </div>
                            </div>
                        </li>
                    <?php } ?>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" onclick="window.location.href = 'index.php?notification=readall'" class="btn btn-danger">Read All</button>

            </div>
        </div>
    </div>
</div>






<script src="plugins/jquery/jquery.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>

<script src="dist/js/adminlte.js"></script>
<script src="php/index.js"></script>
