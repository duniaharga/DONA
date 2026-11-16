#!/usr/bin/env python3
"""Strict JSON syntax and duplicate-key validation. NOT a YOOtheme schema validator."""
import json,sys
from pathlib import Path

def unique(pairs):
    d={}
    for k,v in pairs:
        if k in d: raise ValueError(f"Duplicate JSON key: {k}")
        d[k]=v
    return d

if __name__ == "__main__":
    if len(sys.argv)!=2: sys.exit("Usage: validate_json.py layout.json")
    try:
        value=json.loads(Path(sys.argv[1]).read_text(encoding="utf-8"),object_pairs_hook=unique)
        if not isinstance(value,(dict,list)): raise ValueError("Layout root must be object or array")
        print("PASS: JSON syntax and duplicate keys; YOOtheme schema NOT checked")
    except (OSError,ValueError) as exc:
        sys.exit(f"FAIL: {exc}")
