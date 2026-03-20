#!/usr/bin/env python3
"""0~100 사이의 가상 온도 데이터를 생성하여 MySQL tempdb.temp 테이블에 저장"""

import random
import time
import mysql.connector

DB_CONFIG = {
    "host": "localhost",
    "user": "jiho",
    "password": "qwer1234",
    "database": "tempdb",
}

def insert_temp():
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor()

    temperature = round(random.uniform(0, 100), 2)
    cursor.execute("INSERT INTO temp (temperature) VALUES (%s)", (temperature,))
    conn.commit()

    print(f"[저장] temperature: {temperature}°C")

    cursor.close()
    conn.close()

if __name__ == "__main__":
    print("온도 데이터 생성 시작 (3초 간격, Ctrl+C로 종료)")
    print("-" * 40)
    try:
        while True:
            insert_temp()
            time.sleep(3)
    except KeyboardInterrupt:
        print("\n종료됨.")
