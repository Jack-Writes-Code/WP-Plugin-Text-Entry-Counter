# WP-Plugin-Text-Entry-Counter


# About This Plugin
A WordPress plugin which tracks the number of time a text value is submitted into a form.

The form can be inserted on any page using the shortcode:
```
[text_entry_form]
```

This plugin creates an API route at the endpoint:
yourdomain.com/wp-json/passwords/v1/check
This is then called using jQuery to get the number of times the text has been submitted.


# Live Demo
This plugin was developed for [IsMyPasswordGood.com](https://ismypasswordgood.com/) and can be tested here, but I feel there are other potential uses for it in other projects.


# Notes
Currently the text within the plugin specifically requests passwords to be provided. I will be adding an admin page soon so you can change the text on the form so that users can be prompted to provide other inputs.