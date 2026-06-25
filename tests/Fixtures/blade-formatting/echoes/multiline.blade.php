<div>
    <a href="{{
Route('questions.show', [
                                'question' => $question->id,
                                'username' => $question->to->username,
                            ])
}}">link</a>

    <time>
        {{
            \Carbon\Carbon::parse($question->created_at)
                ->diffForHumans()
        }}
    </time>

    <p>{!!
$rawHtml
    !!}</p>
</div>
