<div>
      <!-- a plain html comment -->
   <p>One</p>
        <!-- {{ $dynamic }} inside an html comment -->
   <!--
        multi-line
        html comment
    -->
      <!--[if IE]>
        <link rel="stylesheet" href="/ie.css" />
    <![endif]-->
    @if ($debug)
        <!-- debug: {{ $debug }} -->
    @endif
   <p>Two</p>
</div>
