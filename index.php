<?php

/**
 * Tool to test Perl-compatible regular expressions (PCRE) in PHP.
 *
 * Copyright 2008, 2011, 2015-2017 Tim Baumgard
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program. If not, see <http://www.gnu.org/licenses/>.
 */

// -----------------------------------------------------------------------------
// Defaults
//

$delimiter = "/";

$modifiers = [
	"caseless" => [
		"label" => "Treat pattern case-insensitively",
		"modifier" => "i",
		"selected" => false],
	"multiline" => [
		"label" => "Enable multi-line mode for anchoring meta-characters",
		"modifier" => "m",
		"selected" => false],
	"dotall" => [
		"label" => "Include newlines in dot meta-character",
		"modifier" => "s",
		"selected" => false],
	"extended" => [
		"label" => "Enable extended mode for white space",
		"modifier" => "x",
		"selected" => false],
	"anchored" => [
		"label" => "Anchor pattern to start of subject",
		"modifier" => "A",
		"selected" => false],
	"dollarEndonly" => [
		"label" => "Anchor pattern to end of subject",
		"modifier" => "D",
		"selected" => false],
	"ungreedy" => [
		"label" => "Invert greediness of quantifiers",
		"modifier" => "U",
		"selected" => false],
	"extra" => [
		"label" => "Treat unnecessary escaping as error",
		"modifier" => "X",
		"selected" => false],
	"infoJchanged" => [
		"label" => "Allow duplicate names for subpatterns",
		"modifier" => "J",
		"selected" => false],
	"utf8" => [
		"label" => "Treat strings as UTF-8",
		"modifier" => "u",
		"selected" => false]];

// -----------------------------------------------------------------------------
// Main
//

$result = null;
$errorMessage = null;

$pattern = $_POST["pattern"] ?? "";
$replacement = $_POST["replacement"] ?? "";
$subject = $_POST["subject"] ?? "";
$action = $_POST["action"] ?? "";
$delimiter = $_POST["delimiter"] ?? $delimiter;

if ($action == "submit") {
	$modifierString = "";

	foreach ($modifiers as $id => $modifier) {
		if (($_POST[$id] ?? "") == "on") {
			$modifiers[$id]["selected"] = true;
			$modifierString .= $modifier["modifier"];
		} else {
			$modifiers[$id]["selected"] = false;
		}
	}

	$isDelimiterValid = @preg_match($delimiter.$delimiter, "") !== false;

	if (!$isDelimiterValid) {
		$errorMessage = "The delimiter is invalid.";
	} else {
		$fullPattern = $delimiter . $pattern . $delimiter . $modifierString;
		$marker = bin2hex(random_bytes(16));

		$callback = function($matches) use ($fullPattern, $replacement, $marker) {
			if (strlen($replacement) > 0) {
				$result = preg_replace($fullPattern, $replacement, $matches[0]);
			} else {
				$result = $matches[0];
			}

			return $marker . $result . $marker;
		};

		$result = @preg_replace_callback($fullPattern, $callback, $subject);

		if ($result === null) {
			$errorMessage = "The pattern is invalid.";
		} else {
			// Escape the result now since HTML will be added in the following step.
			$result = htmlspecialchars($result);

			// Replace the expression markers with semantic HTML markers.
			$htmlPattern = "/{$marker}(.*?){$marker}/ms";
			$htmlReplacement = "<mark>\\1</mark>";
			$result = preg_replace($htmlPattern, $htmlReplacement, $result);
		}
	}
} else if ($action == "clear") {
	$pattern = "";
	$replacement = "";
	$subject = "";
}

?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<title>PHP PCRE Tester</title>
		<link rel="stylesheet" type="text/css" href="style.css" />
	</head>
	<body>
		<header id="header">
			<h1>PHP PCRE Tester</h1>
		</header>

		<?php if (isset($errorMessage)) { ?>
			<section id="error">
				<p role="alert"><b>Error</b>: <?= htmlspecialchars($errorMessage) ?></p>
			</section>
		<?php } ?>

		<main id="content">
			<header>
				<nav class="documentation">
					<ul>
						<li><a href="https://www.php.net/manual/en/book.pcre.php">PHP PCRE Documentation</a></li>
						<li><a href="https://pcre.org/current/doc/html/">PCRE2 Documentation</a></li>
					</ul>
				</nav>
			</header>

			<?php if (isset($result)) { ?>
				<pre class="result"><?= $result ?></pre>
				<hr />
			<?php } ?>

			<form method="post">
				<fieldset class="regex">
					<label for="pattern">Pattern</label>
					<textarea id="pattern" name="pattern" placeholder="\b\w+\b"><?= htmlspecialchars($pattern) ?></textarea>

					<label for="replacement">Replacement</label>
					<textarea id="replacement" name="replacement" placeholder="[\0]"><?= htmlspecialchars($replacement) ?></textarea>

					<label for="subject">Subject</label>
					<textarea id="subject" name="subject" placeholder="Lorem ipsum dolor sit amet, consectetur adipiscing elit."><?= htmlspecialchars($subject) ?></textarea>

					<label for="delimiter">Delimiter</label>
					<input type="text" id="delimiter" name="delimiter" size="1" maxlength="1" required="required" value="<?= htmlspecialchars($delimiter) ?>" />
				</fieldset>

				<fieldset class="modifiers">
					<legend>Pattern Modifiers</legend>

					<?php foreach ($modifiers as $id => $modifier) { ?>
						<span class="modifier">
							<input type="checkbox" id="<?= htmlspecialchars($id) ?>" name="<?= htmlspecialchars($id) ?>" <?php if ($modifier["selected"]) print 'checked="checked"'; ?> />
							<label for="<?= htmlspecialchars($id) ?>">
								(<code><?= htmlspecialchars($modifier["modifier"]) ?></code>)
								<?= htmlspecialchars($modifier["label"]) ?>
							</label>
						</span>
					<?php } ?>
				</fieldset>

				<fieldset class="actions">
					<button type="submit" name="action" value="submit">Submit</button>
					<button type="reset">Reset</button>
					<button type="submit" name="action" value="clear">Clear</button>
				</fieldset>
			</form>
		</main>

		<footer id="footer">
			<p>&copy; 2008, 2011, 2015-2017 Tim Baumgard</p>
		</footer>
	</body>
</html>
