---
layout: default
title: The "upload" control
slug: upload
subtitle: Learn how to create controls using Kirki
mainMaxWidth: 55rem;
bodyClasses: control page
returns: string
heroButtons:
  - url: ../controls
    class: white button round border-only
    icon: fa fa-arrow-circle-o-left
    label: Back to Controls
---

The `upload` control is identical to the [`image`](https://aristath.github.io/kirki/docs/controls/image.html) control, the only difference is that it allows uploading more file types and not only images.

### Usage

```php
<?php
$file_url = get_theme_mod( 'my_setting', '' );
printf( esc_html__( 'URL of uploaded file: %s', 'kirki' ), $file_url );
?>
```
