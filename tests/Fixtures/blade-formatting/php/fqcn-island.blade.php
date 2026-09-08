<div>
    <?php

    $rows = \App\Models\Category::childrenOf($record->id);

    ?>

    <div>{{ count($rows) }} rows</div>
</div>
