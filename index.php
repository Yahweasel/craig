<?PHP
/*
 * Copyright (c) 2017, 2018 Yahweasel
 *
 * Permission to use, copy, modify, and/or distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY
 * SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION
 * OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF OR IN
 * CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

$base = "/home/yahweasel/craig/rec";

// No ID => Send them to homepage
if (!isset($_REQUEST["id"])) {
    header("Location: /home/");
    die();
}
$id = intval($_REQUEST["id"]);

// Make sure the recording exists
if (!file_exists("$base/$id.ogg.header1") ||
    !file_exists("$base/$id.ogg.header2") ||
    !file_exists("$base/$id.ogg.data") ||
    !file_exists("$base/$id.ogg.key") ||
    !file_exists("$base/$id.ogg.delete"))
    die("Invalid ID.");

// Check the key
if (!isset($_REQUEST["key"]))
    die("Invalid ID.");
$key = intval($_REQUEST["key"]);
$corrKey = intval(file_get_contents("$base/$id.ogg.key"));
if ($key !== $corrKey)
    die("Invalid ID.");

// Function to present a download link
function download($title, $format="flac", $container="zip") {
    global $id, $key;
    print "<a href=\"?id=$id&amp;key=$key";
    if ($format === "raw") {
        print "&amp;fetch=raw";
    } else {
        print "&amp;fetch=cooked";
        if ($format !== "flac")
            print "&amp;format=$format";
        if ($container !== "zip")
            print "&amp;container=$container";
    }
    print "\">$title</a> ";
}

// Present them their options
if (!isset($_REQUEST["fetch"]) && !isset($_REQUEST["delete"])) {





?>
<!doctype html>
<html>
    <head>
        <title>Craig Records!</title>
        <style type="text/css">
            .js {
                display: none;
            }

            .big {
                font-size: 1.5em;
            }

            button {
                font-size: 1em;
                background-color: #00eeee;
                font-weight: bold;
                padding: 0.25em;
                border-radius: 0.5em;
                box-shadow: 0 0 0 2px #333333;
            }

            .big button, .local button {
                display: inline-block;
                width: 10em;
                min-height: 3em;
                vertical-align: middle;
                text-align: center;
                color: #000000;
            }

            .local button {
                background-color: #ff9999;
            }

            .lbl {
                display: inline-block;
                text-align: right;
                width: 320px;
            }
        </style>
    </head>
    <body>
        <div style="max-width: 50em;">
        You may fetch your recording in various formats. The most useful
        formats, multi-track FLAC, Vorbis and AAC, are downloadable. <span
        class="js">Other formats, including single-track mixed audio, require
        processing locally in your browser, and thus require a modern browser
        and some patience, and won't work well on mobile devices.</span> Your
        recording ID: <?PHP print $id; ?>
        </div><br/><br/>

        <div class="big">
        <span class="lbl">Multi-track download:&nbsp;</span>
<?PHP
download("FLAC", "flac");
download("Ogg Vorbis", "vorbis");
download("AAC (MPEG-4)", "aac");
?>
        </div><br/><br/>
        
        <div class="local js">
        <span class="lbl">Multi-track processed:&nbsp;</span>
        <button id="mflac">FLAC</button>
        <button id="mm4a">M4A (MPEG-4)</button>
        <button id="mmp3">MP3 (MPEG-1)</button>
        <button id="mwav">wav (uncompressed)</button>
        </div><br/><br/>

        <div class="local js">
        <span class="lbl">Single-track mixed:&nbsp;</span>
        <button id="sflac">FLAC</button>
        <button id="sm4a">M4A (MPEG-4)</button>
        <button id="smp3">MP3 (MPEG-1)</button>
        <button id="swav">wav (uncompressed)</button>
        </div><br/><br/><br/><br/>

        <button id="localProcessingB" class="js">Local processing options</button><br/><br/>

        <div id="localProcessing" style="display: none; padding: 1em;">
            <label for="format">Format:</label>
            <select id="format">
                <option value="flac,flac">FLAC</option>
                <option value="mp4,aac">M4A (MPEG-4)</option>
                <option value="mp3,mp3">MP3 (MPEG-1)</option>
                <option value="wav,pcm_s16le">wav (uncompressed)</option>
            </select><br/><br/>

            <input id="mix" type="checkbox" checked />
            <label for="mix">Mix into single track (defeating Craig's entire purpose)</label><br/><br/>

            <div id="ludditeBox" style="display: none">
                <input id="luddite" type="checkbox" />
                <label for="luddite">
                I am a luddite. I chose MP3 because I am ignorant, and I am
                unwilling to spend even a moment learning what the Hell I'm
                doing. I acknowledge that if I complain about the MP3 file this
                tool produces, or the abusiveness of this message, I will be
                banned. I am an imbecile, and I choose this option as a joyous
                expression of my own stupidity.
                </label><br/><br/>
            </div>

            <div id="wavBox" style="display: none">
                <input id="wav" type="checkbox" />
                <label for="wav">
                Uncompressed audio is big, and this system processes audio
                directly into memory. I promise not to complain if anything
                goes wrong for that reason.
                </label><br/><br/>
            </div>

            <button id="convert">Begin processing</button><br/><br/>

            <div id="ffmstatus" style="background-color: #cccccc; color: #000000;"></div>

            <ul id="ffmoutput"></ul>
        </div><br/><br/>

<?PHP
download("Raw", "raw");
?>
        (Note: Almost no audio editors will support this raw file)

        <script type="text/javascript"><!--
<?PHP
readfile("convert.js");
print "craigOgg=\"?id=" . $id . "&key=" . $key . "&fetch=cooked&format=copy&container=ogg\";\n";
?>
        (function() {
            function gid(id) {
                return document.getElementById(id);
            }

            function replaceA(a) {
                var b = document.createElement("button");
                b.innerHTML = a.innerHTML;
                b.onclick = function() {
                    window.location = a.href;
                };
                a.parentElement.replaceChild(b, a);
            }
            document.querySelectorAll(".big a").forEach(replaceA);

            document.querySelectorAll(".js").forEach(function(e){e.style.display="inline";});

            function vis(id, setTo) {
                var l = gid(id);
                if (!l) return;
                if (!setTo) {
                    if (l.style.display === "none") {
                        setTo = "block";
                    } else {
                        setTo = "none";
                    }
                }
                l.style.display = setTo;
                if (setTo !== "none")
                    l.scrollIntoView();
            }

            gid("localProcessingB").onclick = function() {
                vis("localProcessing");
            }

            var format = gid("format");
            var cb = gid("convert");
            var mix = gid("mix");
            var ludditeBox = gid("ludditeBox");
            var luddite = gid("luddite");
            var wavBox = gid("wavBox");
            var wav = gid("wav");
            var status = gid("ffmstatus");

            luddite.checked = wav.checked = false;

            // Set up the form
            function formatChange() {
                ludditeBox.style.display = "none";
                wavBox.style.display = "none";
                if (format.value === "mp3,mp3") {
                    ludditeBox.style.display = "block";
                } else if (format.value === "wav,pcm_s16le") {
                    wavBox.style.display = "block";
                }
            }
            format.onchange = formatChange;

            function go() {
                if (format.value === "mp3,mp3" && !luddite.checked) {
                    status.innerText = "You must agree to the MP3 terms before performing an MP3 conversion.";
                    return;
                } else if (format.value === "wav,pcm_s16le" && !wav.checked) {
                    status.innerText = "You must agree to the wav terms before performing a wav conversion.";
                    return;
                }

                cb.disabled = true;

                var f = format.value.split(",");
                var opts = {
                    mix: mix.checked,
                    callback: function(){cb.disabled = false;}
                };
                craigFfmpeg(craigOgg, f[0], f[1], opts);
            }
            cb.onclick = go;

            // And map all the buttons
            var bmap = {
                "flac": "flac,flac",
                "m4a": "mp4,aac",
                "mp3": "mp3,mp3",
                "wav": "wav,pcm_s16le"
            };

            function mapButton(id) {
                var b = gid(id);
                console.log(b);
                if (!b) return;
                var mixed = (id[0] === "s");
                var bformat = bmap[id.substr(1)];
                b.onclick = function() {
                    if (cb.disabled)
                        return;
                    format.value = bformat;
                    formatChange();
                    mix.checked = mixed;
                    vis("localProcessing", "block");
                    go();
                }
            }

            Object.keys(bmap).forEach(function(t){mapButton("m"+t);mapButton("s"+t);});
        })();
        --></script>
    </body>
</html>
<?PHP





} else if (isset($_REQUEST["delete"])) {
    $deleteKey = intval($_REQUEST["delete"]);
    $corrDeleteKey = intval(file_get_contents("$base/$id.ogg.delete"));
    if ($deleteKey !== $corrDeleteKey) {
        die("Invalid ID.");
    }

    if (!isset($_REQUEST["sure"])) {
?>
<!doctype html><html><head><title>Craig Records!</title></head><body>
This will DELETE recording <?PHP print $id; ?>! Are you sure?<br/><br/>
<a href="?id=<?PHP print $id; ?>&amp;key=<?PHP print $key; ?>&amp;delete=<?PHP print $deleteKey; ?>&amp;sure=yes">Yes</a><br/><br/>
<a href="?id=<?PHP print $id; ?>&amp;key=<?PHP print $key; ?>">No</a>
</body></html>
<?PHP

    } else {
        // Delete it!
        unlink("$base/$id.ogg.header1");
        unlink("$base/$id.ogg.header2");
        unlink("$base/$id.ogg.data");
        unlink("$base/$id.ogg.delete");
        // We do NOT delete the key, so that it's not replaced before it times out naturally
        die("Deleted!");
    }

} else if ($_REQUEST["fetch"] === "cooked") {
    $format = "flac";
    if (isset($_REQUEST["format"])) {
        if ($_REQUEST["format"] === "copy" ||
            $_REQUEST["format"] === "aac" ||
            $_REQUEST["format"] === "vorbis" ||
            $_REQUEST["format"] === "ra")
            $format = $_REQUEST["format"];
    }
    $container="zip";
    $ext="$format.zip";
    $mime="application/zip";
    if (isset($_REQUEST["container"])) {
        if ($_REQUEST["container"] === "ogg") {
            $container = "ogg";
            $ext = "ogg";
            $mime = "audio/ogg";
        } else if ($_REQUEST["container"] === "matroska") {
            $container = "matroska";
            $ext = "mkv";
            $mime = "video/x-matroska";
        }
    }
    header("Content-disposition: attachment; filename=$id.$ext");
    header("Content-type: $mime");
    ob_flush();
    flush();
    passthru("/home/yahweasel/craig/cook.sh $id $format $container");

} else {
    header("Content-disposition: attachment; filename=$id.ogg");
    header("Content-type: audio/ogg");
    readfile("$base/$id.ogg.header1");
    readfile("$base/$id.ogg.header2");
    readfile("$base/$id.ogg.data");

}
?>
