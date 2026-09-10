import sys
import joblib
import numpy as np

model = joblib.load("model.pkl")

math = int(sys.argv[1])
science = int(sys.argv[2])
english = int(sys.argv[3])
att = int(sys.argv[4])
study = int(sys.argv[5])

data = np.array([[math,science,english,att,study]])

res = model.predict(data)

print(round(res[0],2))
