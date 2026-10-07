<?php

it('returns a successful response', function () {
    $response = $this->get('/ar');

    $response->assertStatus(200);
});
