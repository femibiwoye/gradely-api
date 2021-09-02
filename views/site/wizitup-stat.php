<?php
$this->title = 'WizItUp stats';
?>

<?php if($type == 'user'){?>
<table class="table table-striped table-hover table-bordered">
    <thead>
    <tr>
        <th>S/N (<?=count($data)?>)</th>
        <th>User ID</th>
        <th>Content ID</th>
        <th>Subscription Plan</th>
        <th>Subscription Expiry</th>
        <th>Firstname</th>
        <th>Lastname</th>
        <th>Video count</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($data as $key=>$item) { ?>
        <tr>
            <td><?=$key+1?></td>
            <td><?=$item['user_id']?></td>
            <td><?=$item['content_id']?></td>
            <td><?=$item['subscription_plan']?></td>
            <td><?=$item['subscription_expiry']?></td>
            <td><?=$item['firstname']?></td>
            <td><?=$item['lastname']?></td>
            <td><?=$item['code']?></td>
        </tr>
    <?php } ?>
    </tbody>
</table>
<?php }else{ ?>

    <table class="table table-striped table-hover table-bordered">
        <thead>
        <tr>
            <th>S/N (<?=count($data)?>)</th>
            <th>User count</th>
            <th>Content ID</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($data as $key=>$item) { ?>
            <tr>
                <td><?=$key+1?></td>
                <td><?=$item['user_count']?></td>
                <td><?=$item['content_id']?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

<?php } ?>
