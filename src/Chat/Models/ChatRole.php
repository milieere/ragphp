<?php

namespace App\Chat\Models;

enum ChatRole: string
{
    case Bot = 'Bot';
    case Human = 'Human';
}