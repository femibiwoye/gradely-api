<?php

return [
    'GET v2/commands/games' => 'v2/commands/games',
    'GET v2/student/catchup/game/<token:[a-zA-Z0-9-/]+>' => 'v2/student/catchup/game',
    'GET v2/student/catchup/game-link/<token:[a-zA-Z0-9-/]+>' => 'v2/student/catchup/game-link',
    'POST v2/student/catchup/game-like/<token:[a-zA-Z0-9-/]+>' => 'v2/student/catchup/game-like',

];
