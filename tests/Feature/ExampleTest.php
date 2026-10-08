<?php

beforeEach(fn () => $this->withoutVite());

it('returns a successful response', function () {
    $response = $this->get('/ar');

    $response->assertStatus(200);
});
