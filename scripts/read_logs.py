#!/usr/bin/env python3
import sys
import os
import re
import csv
from datetime import datetime
import locale

locale.setlocale(locale.LC_TIME, 'C')

def read_log(log_path, date_str, keyword='', ip='', log_type='access'):
    if not os.path.exists(log_path):
        return []

    try:
        date_obj = datetime.strptime(date_str, '%Y-%m-%d')

        if log_type == 'access':
            date_search = date_obj.strftime('[%d/%b/%Y')  # Ex: [08/Jun/2025
        else:
            date_search = date_obj.strftime('%Y/%m/%d')   # Ex: 2025/06/08

        with open(log_path, 'r') as f:
            lines = f.readlines()

        filtered = []
        for line in lines:
            if date_search not in line:
                continue
            if keyword and keyword.lower() not in line.lower():
                continue
            if ip and ip not in line:
                continue
            filtered.append(line.strip())

        return filtered

    except Exception as e:
        return [f"ERREUR: {str(e)}"]

def parse_access_log_line(line):
    # Regex 
    m = re.match(r'^(\S+) \S+ \S+ \[([^\]]+)\] "(\S+) ([^"]+) HTTP/[\d.]+" (\d+) (\d+) "([^"]*)" "([^"]*)"', line)
    if not m:
        return None
    return {
        'IP': m.group(1),
        'Date': m.group(2),
        'Méthode': m.group(3),
        'URL': m.group(4),
        'Code HTTP': m.group(5),
        'Taille': m.group(6),
        'Referer': m.group(7),
        'User-Agent': m.group(8),
    }

if __name__ == '__main__':
    if len(sys.argv) < 3:
        print("Usage: read_logs.py [access|error] YYYY-MM-DD [keyword] [ip] [csv]")
        sys.exit(1)

    log_type = sys.argv[1].lower()
    date_str = sys.argv[2]
    keyword = sys.argv[3] if len(sys.argv) > 3 else ''
    ip = sys.argv[4] if len(sys.argv) > 4 else ''
    output_csv = (len(sys.argv) > 5 and sys.argv[5] == "csv")

    logs_paths = {
        'access': '/var/log/nginx/access.log',
        'error': '/var/log/nginx/error.log'
    }

    if log_type not in logs_paths:
        print("Type de log invalide. Choix : access ou error")
        sys.exit(1)

    lines = read_log(logs_paths[log_type], date_str, keyword, ip, log_type)

    if output_csv and log_type == "access":
        # Export CSV lisible
        fieldnames = ['IP', 'Date', 'Méthode', 'URL', 'Code HTTP', 'Taille', 'Referer', 'User-Agent']
        writer = csv.DictWriter(sys.stdout, fieldnames=fieldnames)
        writer.writeheader()
        for line in lines:
            parsed = parse_access_log_line(line)
            if parsed:
                writer.writerow(parsed)
    else:
        for line in lines:
            print(line)
