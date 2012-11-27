#!/bin/sh
tar -cjf releases/release.tar.bz2 $(git diff --name-only HEAD^! --diff-filter=MA)
