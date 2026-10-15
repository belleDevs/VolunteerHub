<?php

return [

    /*
    | Stored timestamps stay in UTC. The admin portal shows them in
    | Philippine time because VolunteerHub serves Region XIII Surigao.
    */
    'display_timezone' => 'Asia/Manila',

    /*
    | Logs are kept for this many months. The audit:prune command removes
    | older rows. The admin page cannot edit or delete entries.
    */
    'retention_months' => 24,

];
